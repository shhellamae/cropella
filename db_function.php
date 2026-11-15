<?php
    class Wala {
        private $pdo;
        private $currentUserId;

        public function __construct($pdo, $userId = null) {
            $this->pdo = $pdo;
            $this->currentUserId = $userId ?? ($_SESSION['user_id'] ?? 1);
        }

        // ==================== AUDIT LOGGER ====================
        // private function logAudit($tableName, $recordId, $action) {
        //     try {
        //         $stmt = $this->pdo->prepare("
        //             INSERT INTO audit_log (user_id, table_name, record_id, action) 
        //             VALUES (?, ?, ?, ?)
        //         ");
        //         $stmt->execute([$this->currentUserId, $tableName, $recordId, $action]);
        //     } catch (\Exception $e) {
        //         // Silently catch audit exceptions to prevent crashing main transaction
        //     }
        // }


        // =========================================================================
        // 1. ALL GET / SELECT METHODS (Queries & Reports)
        // =========================================================================

        // --- Lookups & References ---
        public function getAllSoilTypes() {
            $stmt = $this->pdo->query("SELECT * FROM soil_type ORDER BY soil_description");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllIrrigationTypes() {
            $stmt = $this->pdo->query("SELECT * FROM irrigation_type ORDER BY irrigation_description");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllCropCategories() {
            $stmt = $this->pdo->query("SELECT * FROM crop_category ORDER BY category_description");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllSeasons() {
            $stmt = $this->pdo->query("SELECT * FROM recommended_season ORDER BY season_description");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllPlantingStatuses() {
            $stmt = $this->pdo->query("SELECT * FROM planting_status ORDER BY status_description");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllQualityGrades() {
            $stmt = $this->pdo->query("SELECT * FROM quality_grade ORDER BY grade_description");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllHarvestRemarks() {
            $stmt = $this->pdo->query("SELECT * FROM harvest_remark ORDER BY remark_description");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllUserRoles() {
            $stmt = $this->pdo->query("SELECT * FROM user_role ORDER BY role_description");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // --- Core Entity Gets ---
        public function getAllUsers() {
            $stmt = $this->pdo->query("
                SELECT u.user_id, u.username, u.full_name, u.role_id, ur.role_description AS role 
                FROM users u
                LEFT JOIN user_role ur ON u.role_id = ur.role_id
                ORDER BY u.user_id DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllFarmers($sort = 'DESC') {
            $sort = (strtoupper($sort) === 'ASC') ? 'ASC' : 'DESC';
            $stmt = $this->pdo->prepare("SELECT * FROM farmer ORDER BY farmer_id $sort");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllFarms($sort = 'DESC') {
            $sort = (strtoupper($sort) === 'ASC') ? 'ASC' : 'DESC';
            $stmt = $this->pdo->prepare("
                SELECT 
                    f.farm_id, f.farmer_id, f.farm_name, f.location, f.area_hectares, f.soil_id, f.irrigation_id,
                    fr.first_name, fr.last_name,
                    st.soil_description AS soil_type,
                    it.irrigation_description AS irrigation_type
                FROM farm f
                JOIN farmer fr ON f.farmer_id = fr.farmer_id
                LEFT JOIN soil_type st ON f.soil_id = st.soil_id
                LEFT JOIN irrigation_type it ON f.irrigation_id = it.irrigation_id
                ORDER BY f.farm_id $sort
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllCrops($sort = 'DESC') {
            $sort = (strtoupper($sort) === 'ASC') ? 'ASC' : 'DESC';
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.crop_id, c.crop_name, c.variety, c.category_id, c.growing_period_days, c.season_id,
                    cc.category_description AS crop_category,
                    rs.season_description AS recommended_season
                FROM crop c
                LEFT JOIN crop_category cc ON c.category_id = cc.category_id
                LEFT JOIN recommended_season rs ON c.season_id = rs.season_id
                ORDER BY c.crop_id $sort
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllPlantings($sort = 'DESC') {
            $sort = (strtoupper($sort) === 'ASC') ? 'ASC' : 'DESC';
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.planting_id, p.farm_id, p.crop_id, p.planting_date, p.area_planted, 
                    p.seed_quantity, p.expected_harvest_date, p.status_id,
                    f.farm_name, c.crop_name, c.variety,
                    ps.status_description AS planting_status
                FROM planting p
                JOIN farm f ON p.farm_id = f.farm_id
                JOIN crop c ON p.crop_id = c.crop_id
                LEFT JOIN planting_status ps ON p.status_id = ps.status_id
                ORDER BY p.planting_id $sort
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllHarvests($sort = 'DESC') {
            $sort = (strtoupper($sort) === 'ASC') ? 'ASC' : 'DESC';
            $stmt = $this->pdo->prepare("
                SELECT 
                    h.harvest_id, h.planting_id, h.harvest_date, h.quantity_kg, 
                    h.selling_price_per_kg, h.grade_id, h.remark_id,
                    p.planting_date, f.farm_name, c.crop_name, c.variety,
                    qg.grade_description AS quality_grade,
                    hr.remark_description AS harvest_remark
                FROM harvest h
                JOIN planting p ON h.planting_id = p.planting_id
                JOIN farm f ON p.farm_id = f.farm_id
                JOIN crop c ON p.crop_id = c.crop_id
                LEFT JOIN quality_grade qg ON h.grade_id = qg.grade_id
                LEFT JOIN harvest_remark hr ON h.remark_id = hr.remark_id
                ORDER BY h.harvest_id $sort
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllAuditLogs($sort = 'DESC') {
            $sort = (strtoupper($sort) === 'ASC') ? 'ASC' : 'DESC';
            $stmt = $this->pdo->prepare("
                SELECT 
                    al.log_id, al.user_id, al.table_name, al.record_id, al.action, al.action_at, TO_CHAR(al.action_at, 'YYYY-MM-DD HH24:MI:SS') AS action_at,
                    u.username, u.full_name, ur.role_description
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.user_id
                LEFT JOIN user_role ur ON u.role_id = ur.role_id
                ORDER BY al.log_id $sort
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // --- Reports ---
        public function getReportFarmersAndFarms($search = '') {
            $query = "
                SELECT fr.farmer_id, fr.first_name, fr.last_name, fr.contact_number, fr.address,
                    f.farm_id, f.farm_name, f.location, f.area_hectares
                FROM farmer fr
                LEFT JOIN farm f ON fr.farmer_id = f.farmer_id
            ";
            if (!empty($search)) {
                $query .= " WHERE (fr.first_name ILIKE ? OR fr.last_name ILIKE ? OR f.farm_name ILIKE ? OR f.location ILIKE ?)";
                $stmt = $this->pdo->prepare($query);
                $term = "%$search%";
                $stmt->execute([$term, $term, $term, $term]);
            } else {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getReportCropsPlantedPerFarm() {
            $stmt = $this->pdo->prepare("
                SELECT f.farm_id, f.farm_name, fr.first_name, fr.last_name, 
                    c.crop_name, c.variety, p.planting_date, p.area_planted, ps.status_description AS planting_status
                FROM planting p
                JOIN farm f ON p.farm_id = f.farm_id
                JOIN farmer fr ON f.farmer_id = fr.farmer_id
                JOIN crop c ON p.crop_id = c.crop_id
                LEFT JOIN planting_status ps ON p.status_id = ps.status_id
                ORDER BY f.farm_name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getReportPlantingsByStatus() {
            $stmt = $this->pdo->prepare("
                SELECT ps.status_description AS planting_status, COUNT(p.planting_id) AS total_plantings, SUM(p.area_planted) AS total_area_planted
                FROM planting p
                LEFT JOIN planting_status ps ON p.status_id = ps.status_id
                GROUP BY ps.status_description
                ORDER BY total_plantings DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getReportHarvests($startDate = '', $endDate = '') {
            $query = "
                SELECT h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                    (h.quantity_kg * h.selling_price_per_kg) AS estimated_value,
                    f.farm_name, c.crop_name, qg.grade_description AS quality_grade, hr.remark_description AS harvest_remark
                FROM harvest h
                JOIN planting p ON h.planting_id = p.planting_id
                JOIN farm f ON p.farm_id = f.farm_id
                JOIN crop c ON p.crop_id = c.crop_id
                LEFT JOIN quality_grade qg ON h.grade_id = qg.grade_id
                LEFT JOIN harvest_remark hr ON h.remark_id = hr.remark_id
            ";
            $params = [];
            if (!empty($startDate) && !empty($endDate)) {
                $query .= " WHERE h.harvest_date BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            $query .= " ORDER BY h.harvest_date DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }


        // =========================================================================
        // 2. ALL ADD / INSERT METHODS
        // =========================================================================
    public function createUser($data) {
        // 1. Check if username already exists
        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $checkStmt->execute([$data['username']]);
        if ($checkStmt->fetchColumn() > 0) {
            return false; // Username already exists
        }

        // 2. Proceed with insertion using plain text password
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, full_name, password, role_id)
            VALUES (?, ?, ?, ?)
            RETURNING user_id
        ");
        
        $stmt->execute([
            $data['username'],
            $data['full_name'],
            $data['password'], // Stored as plain text
            $data['role_id']
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && $row['user_id']) {
            // Trigger audit log for user creation
            $this->logAudit('users', $row['user_id'], 'INSERT');
            return $row['user_id'];
        }
        return false;
    }

        public function addFarmer($data) {
            $stmt = $this->pdo->prepare("
                INSERT INTO farmer (first_name, last_name, contact_number, address, date_registered)
                VALUES (?, ?, ?, ?, CURRENT_DATE)
                RETURNING farmer_id
            ");
            $stmt->execute([$data['first_name'], $data['last_name'], $data['contact_number'], $data['address']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) { $this->logAudit('farmer', $row['farmer_id'], 'INSERT'); }
        }

        public function addFarm($data) {
            $stmt = $this->pdo->prepare("
                INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_id, irrigation_id)
                VALUES (?, ?, ?, ?, ?, ?)
                RETURNING farm_id
            ");
            $stmt->execute([
                $data['farmer_id'], $data['farm_name'], $data['location'], $data['area_hectares'],
                $data['soil_id'] !== '' ? $data['soil_id'] : null,
                $data['irrigation_id'] !== '' ? $data['irrigation_id'] : null
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) { $this->logAudit('farm', $row['farm_id'], 'INSERT'); }
        }

        public function addCrop($data) {
            $stmt = $this->pdo->prepare("
                INSERT INTO crop (crop_name, variety, category_id, growing_period_days, season_id)
                VALUES (?, ?, ?, ?, ?)
                RETURNING crop_id
            ");
            $stmt->execute([
                $data['crop_name'], $data['variety'],
                $data['category_id'] !== '' ? $data['category_id'] : null,
                $data['growing_period_days'],
                $data['season_id'] !== '' ? $data['season_id'] : null
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) { $this->logAudit('crop', $row['crop_id'], 'INSERT'); }
        }

        public function addPlanting($data) {
            $stmt = $this->pdo->prepare("
                INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                RETURNING planting_id
            ");
            $stmt->execute([
                $data['farm_id'], $data['crop_id'], $data['planting_date'], $data['area_planted'],
                $data['seed_quantity'], $data['expected_harvest_date'],
                $data['status_id'] !== '' ? $data['status_id'] : null
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) { $this->logAudit('planting', $row['planting_id'], 'INSERT'); }
        }

        public function addHarvest($data) {
            $stmt = $this->pdo->prepare("
                INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, grade_id, remark_id)
                VALUES (?, ?, ?, ?, ?, ?)
                RETURNING harvest_id
            ");
            $stmt->execute([
                $data['planting_id'], $data['harvest_date'], $data['quantity_kg'], $data['selling_price_per_kg'],
                $data['grade_id'] !== '' ? $data['grade_id'] : null,
                $data['remark_id'] !== '' ? $data['remark_id'] : null
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) { $this->logAudit('harvest', $row['harvest_id'], 'INSERT'); }
        }


        // =========================================================================
        // 3. ALL UPDATE METHODS
        // =========================================================================

        public function updateUser($data) {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET full_name = ?, role_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
            $stmt->execute([
                $data['full_name'], 
                $data['role_id'], 
                $data['user_id']
            ]);
            $this->logAudit('users', $data['user_id'], 'UPDATE');
            return true;
        }

        public function updatePassword($userId, $currentPassword, $newPassword) {
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($currentPassword, $user['password'])) {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $this->pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $result = $update->execute([$hashed, $userId]);
                if ($result) {
                    $this->logAudit('users', $userId, 'UPDATE_PASSWORD');
                    return true;
                }
            }
            return false;
        }

        public function updateFarmer($data) {
            $stmt = $this->pdo->prepare("
                UPDATE farmer 
                SET first_name = ?, last_name = ?, contact_number = ?, address = ?, updated_at = CURRENT_TIMESTAMP
                WHERE farmer_id = ?
            ");
            $stmt->execute([$data['first_name'], $data['last_name'], $data['contact_number'], $data['address'], $data['farmer_id']]);
            $this->logAudit('farmer', $data['farmer_id'], 'UPDATE');
        }

        public function updateFarm($data) {
            $stmt = $this->pdo->prepare("
                UPDATE farm 
                SET farmer_id = ?, farm_name = ?, location = ?, area_hectares = ?, soil_id = ?, irrigation_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE farm_id = ?
            ");
            $stmt->execute([
                $data['farmer_id'], $data['farm_name'], $data['location'], $data['area_hectares'],
                $data['soil_id'] !== '' ? $data['soil_id'] : null,
                $data['irrigation_id'] !== '' ? $data['irrigation_id'] : null,
                $data['farm_id']
            ]);
            $this->logAudit('farm', $data['farm_id'], 'UPDATE');
        }

        public function updateCrop($data) {
            $stmt = $this->pdo->prepare("
                UPDATE crop 
                SET crop_name = ?, variety = ?, category_id = ?, growing_period_days = ?, season_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE crop_id = ?
            ");
            $stmt->execute([
                $data['crop_name'], $data['variety'],
                $data['category_id'] !== '' ? $data['category_id'] : null,
                $data['growing_period_days'],
                $data['season_id'] !== '' ? $data['season_id'] : null,
                $data['crop_id']
            ]);
            $this->logAudit('crop', $data['crop_id'], 'UPDATE');
        }

        public function updatePlanting($data) {
            $stmt = $this->pdo->prepare("
                UPDATE planting 
                SET farm_id = ?, crop_id = ?, planting_date = ?, area_planted = ?, seed_quantity = ?, expected_harvest_date = ?, status_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE planting_id = ?
            ");
            $stmt->execute([
                $data['farm_id'], $data['crop_id'], $data['planting_date'], $data['area_planted'],
                $data['seed_quantity'], $data['expected_harvest_date'],
                $data['status_id'] !== '' ? $data['status_id'] : null,
                $data['planting_id']
            ]);
            $this->logAudit('planting', $data['planting_id'], 'UPDATE');
        }

        public function updateHarvest($data) {
            $stmt = $this->pdo->prepare("
                UPDATE harvest 
                SET planting_id = ?, harvest_date = ?, quantity_kg = ?, selling_price_per_kg = ?, grade_id = ?, remark_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE harvest_id = ?
            ");
            $stmt->execute([
                $data['planting_id'], $data['harvest_date'], $data['quantity_kg'], $data['selling_price_per_kg'],
                $data['grade_id'] !== '' ? $data['grade_id'] : null,
                $data['remark_id'] !== '' ? $data['remark_id'] : null,
                $data['harvest_id']
            ]);
            $this->logAudit('harvest', $data['harvest_id'], 'UPDATE');
        }


        // =========================================================================
        // 4. ALL DELETE METHODS
        // =========================================================================

        public function deleteUser($id) {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$id]);
            $this->logAudit('users', $id, 'DELETE');
            return true;
        }

        public function deleteFarmer($id) {
            $stmt = $this->pdo->prepare("DELETE FROM farmer WHERE farmer_id = ?");
            $success = $stmt->execute([$id]);
            
            if ($success) {
                $this->logAudit('farmer', $id, 'DELETE');
                return true;
            }
            return false;
        }

        public function deleteFarm($id) {
            $stmt = $this->pdo->prepare("DELETE FROM farm WHERE farm_id = ?");
            $success = $stmt->execute([$id]);
            
            if ($success) {
                $this->logAudit('farm', $id, 'DELETE');
                return true;
            }
            return false;
        }

        public function deleteCrop($id) {
            $stmt = $this->pdo->prepare("DELETE FROM crop WHERE crop_id = ?");
            $success = $stmt->execute([$id]);
            
            if ($success) {
                $this->logAudit('crop', $id, 'DELETE');
                return true;
            }
            return false;
        }

        public function deletePlanting($id) {
            $stmt = $this->pdo->prepare("DELETE FROM planting WHERE planting_id = ?");
            $success = $stmt->execute([$id]);
            
            if ($success) {
                $this->logAudit('planting', $id, 'DELETE');
                return true;
            }
            return false;
        }

        public function deleteHarvest($id) {
            $stmt = $this->pdo->prepare("DELETE FROM harvest WHERE harvest_id = ?");
            $success = $stmt->execute([$id]);
            
            if ($success) {
                $this->logAudit('harvest', $id, 'DELETE');
                return true;
            }
            return false;
        }
        public function updateUserStatus($userId, $status) {
            $stmt = $this->pdo->prepare("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $success = $stmt->execute([$status, $userId]);
            
            if ($success) {
                // Use short action strings to prevent hitting database VARCHAR length limits
                $actionName = ($status === 'Deactivated') ? 'DEACTIVATE' : 'ACTIVATE';
                $this->logAudit('users', $userId, $actionName);
                return true;
            }
            return false;
        }
        // ==================== AUDIT LOGGER ====================
        private function logAudit($tableName, $recordId, $action) {
        // Removed try-catch temporarily to expose any hidden database errorsp
        $stmt = $this->pdo->prepare("
            INSERT INTO audit_log (user_id, table_name, record_id, action) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$this->currentUserId, $tableName, $recordId, $action]);
    }
    // private function logAudit($tableName, $recordId, $action) {
        //     try {
        //         $stmt = $this->pdo->prepare("
        //             INSERT INTO audit_log (user_id, table_name, record_id, action) 
        //             VALUES (?, ?, ?, ?)
        //         ");
        //         $stmt->execute([$this->currentUserId, $tableName, $recordId, $action]);
        //     } catch (\Exception $e) {
        //         // Silently catch audit exceptions to prevent crashing main transaction
        //     }
        // }
    }
?>