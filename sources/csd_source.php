<?php
// sources/csd_source.php
// College Sports Directory data source implementation

// Make sure we have the DataSource base class
require_once dirname(__FILE__) . '/data_source.php';

/**
 * College Sports Directory Data Source
 * This class handles the integration with the College Sports Directory database
 */
class CSDSource extends DataSource {
    /**
     * Get the name of this data source
     * 
     * @return string Source name
     */
    public function getName() {
        return 'csd';
    }
    
    /**
     * Check if the data source is available
     * 
     * @return bool True if available, false otherwise
     */
    public function isAvailable() {
        // For CSD, we just need to check if the database tables exist and have data
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM information_schema.tables 
             WHERE table_schema = DATABASE() 
             AND table_name IN ('csd_schools', 'csd_staff', 'csd_school_staff')"
        );
        
        return isset($result['count']) && $result['count'] == 3;
    }
    
    /**
     * Get school data from College Sports Directory
     * 
     * @param string $domain School email domain
     * @return array School data
     */
    public function getSchoolData($domain) {
        // Get the credentials and table prefix
        $credentials = $this->getCredentials();
        $additionalData = !empty($credentials['additional_data']) 
            ? json_decode($credentials['additional_data'], true) 
            : [];
        
        $tablePrefix = $additionalData['db_prefix'] ?? 'csd_';
        
        // First, find staff members with the given email domain
        $staffQuery = "SELECT * FROM {$tablePrefix}staff WHERE email LIKE ?";
        $staffMembers = $this->db->select($staffQuery, ["%@{$domain}"]);
        
        // If no direct email match, check for a close domain match (e.g., umich.edu vs michigan.edu)
        if (empty($staffMembers)) {
            // Generate domain variations for fuzzy matching
            $domainParts = explode('.', $domain);
            $baseDomain = $domainParts[0];
            
            // Common domain variations
            $variations = [
                "%" . $baseDomain . "%",      // Basic partial match
                "%u" . $baseDomain . "%",     // u + domain (e.g., umich for michigan)
                "%" . substr($baseDomain, 0, 3) . "%" // First 3 chars (e.g., mic for michigan)
            ];
            
            // Try each variation
            foreach ($variations as $variation) {
                $fuzzyStaffQuery = "SELECT * FROM {$tablePrefix}staff WHERE email LIKE ?";
                $fuzzyStaffMembers = $this->db->select($fuzzyStaffQuery, [$variation]);
                
                if (!empty($fuzzyStaffMembers)) {
                    $staffMembers = $fuzzyStaffMembers;
                    logMessage("CSD: No exact domain match for '{$domain}', using fuzzy match: '{$variation}'", 'info');
                    break;
                }
            }
        }
        
        // If still no staff members found, try to search by school name derived from domain
        if (empty($staffMembers)) {
            $schoolName = getSchoolNameFromDomain($domain);
            
            // Log the attempt
            logMessage("CSD: No staff found with domain '{$domain}', trying school name: '{$schoolName}'", 'info');
            
            // Try to find school by name
            $schoolQuery = "SELECT * FROM {$tablePrefix}schools WHERE school_name LIKE ?";
            $school = $this->db->selectOne($schoolQuery, ["%{$schoolName}%"]);
            
            if (!$school) {
                // One last attempt - try to match on shorter school name
                $shortName = preg_replace('/\s+university$|\s+college$/i', '', $schoolName);
                if ($shortName != $schoolName) {
                    $schoolQuery = "SELECT * FROM {$tablePrefix}schools WHERE school_name LIKE ?";
                    $school = $this->db->selectOne($schoolQuery, ["%{$shortName}%"]);
                }
                
                if (!$school) {
                    return [
                        'success' => false,
                        'message' => "School not found in College Sports Directory for domain '{$domain}'",
                        'data' => null
                    ];
                }
            }
            
            // Get staff members for this school
            $staffQuery = "SELECT s.* FROM {$tablePrefix}staff s
                           JOIN {$tablePrefix}school_staff ss ON s.id = ss.staff_id
                           WHERE ss.school_id = ?";
            $staffMembers = $this->db->select($staffQuery, [$school['id']]);
        } else {
            // Get school IDs from staff members
            $staffIds = array_column($staffMembers, 'id');
            
            if (empty($staffIds)) {
                return [
                    'success' => false,
                    'message' => 'No valid staff IDs found for the domain',
                    'data' => null
                ];
            }
            
            $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
            
            $schoolIdsQuery = "SELECT DISTINCT school_id FROM {$tablePrefix}school_staff 
                               WHERE staff_id IN ({$placeholders})";
            $schoolIdsResult = $this->db->select($schoolIdsQuery, $staffIds);
            
            if (empty($schoolIdsResult)) {
                return [
                    'success' => false,
                    'message' => 'School not found for staff members',
                    'data' => null
                ];
            }
            
            // Extract just the school_id values
            $schoolIds = [];
            foreach ($schoolIdsResult as $row) {
                $schoolIds[] = $row['school_id'];
            }
            
            // Get the most common school ID (in case staff members belong to different schools)
            $schoolIdCount = array_count_values($schoolIds);
            arsort($schoolIdCount);
            $mostCommonSchoolId = key($schoolIdCount);
            
            // Get school information
            $schoolQuery = "SELECT * FROM {$tablePrefix}schools WHERE id = ?";
            $school = $this->db->selectOne($schoolQuery, [$mostCommonSchoolId]);
            
            if (!$school) {
                return [
                    'success' => false,
                    'message' => 'School not found for the given staff members',
                    'data' => null
                ];
            }
            
            // Get all staff members for this school
            $allStaffQuery = "SELECT s.* FROM {$tablePrefix}staff s
                             JOIN {$tablePrefix}school_staff ss ON s.id = ss.staff_id
                             WHERE ss.school_id = ?";
            $staffMembers = $this->db->select($allStaffQuery, [$school['id']]);
        }
        
        // Process staff names - parse full_name into components
        if (!empty($staffMembers)) {
            foreach ($staffMembers as &$staff) {
                // Check if we need to parse the name
                if (isset($staff['full_name']) && 
                    (!isset($staff['first_name']) || !isset($staff['last_name']))) {
                    
                    // Parse the full name
                    $parsedName = parseFullName($staff['full_name']);
                    
                    // Add the parsed components to the staff record
                    $staff['title'] = $parsedName['title'];
                    $staff['first_name'] = $parsedName['first_name'];
                    $staff['middle_name'] = $parsedName['middle_name'];
                    $staff['last_name'] = $parsedName['last_name'];
                    $staff['suffix'] = $parsedName['suffix'];
                }
            }
            unset($staff); // Important to unset the reference after foreach
        }
        
        return [
            'success' => true,
            'message' => 'School data retrieved successfully',
            'data' => [
                'school' => $school,
                'staff' => $staffMembers,
                'total_staff' => count($staffMembers)
            ]
        ];
    }
}
?>