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
        // Extract school name from domain for searching
        $schoolName = getSchoolNameFromDomain($domain);
        
        // First, try to find the school by domain in the csd_schools table
        $school = $this->db->selectOne(
            "SELECT * FROM csd_schools WHERE school_domain = ? OR school_name LIKE ?",
            [$domain, "%$schoolName%"]
        );
        
        if (!$school) {
            return [
                'success' => false,
                'message' => 'School not found in College Sports Directory',
                'data' => null
            ];
        }
        
        // Get staff members for this school
        $staff = $this->db->select(
            "SELECT s.* FROM csd_staff s
             JOIN csd_school_staff ss ON s.staff_id = ss.staff_id
             WHERE ss.school_id = ?",
            [$school['school_id']]
        );
        
        return [
            'success' => true,
            'message' => 'School data retrieved successfully',
            'data' => [
                'school' => $school,
                'staff' => $staff,
                'total_staff' => count($staff)
            ]
        ];
    }
}
?>
