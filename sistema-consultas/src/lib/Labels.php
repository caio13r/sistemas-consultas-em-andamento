<?php

    namespace Cfo\SisConsultas\lib;

    use Cfo\SisConsultas\lib\Session;
    use Cfo\SisConsultas\database\Database1;
    use Cfo\SisConsultas\database\Database4;

    use PDO;
    use PDOException;
    use Exception;

    class Labels {

        private $db;
        private $redis;

        public function __construct(){
            $this->db = Database1::getInstance();
            $con = $this->db->getConnection();
            $this->db = $con;

            $this->redis = Database4::getInstance();
            $this->redis = $this->redis->getConnection();
        }

        public function selectLabels() {
            try {

                $sql = "
                    SELECT
                        id AS label_id,
                        label_value AS nome_label,
                        description AS descricao,
                        label_key AS key_label,
                        url AS url,
                        class AS icon,
                        disable AS disabled,
                        display_order,
                        LEFT(label_key, 2) AS sigla
                    FROM
                        tbl_labels
                    ORDER BY
                        COALESCE(display_order, id) ASC;
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $labels = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return $labels;
            } catch (PDOException $error) {
                return $labels = "Erro: " . $error->getMessage();
            }
        }

        public function getChildLabels() {
            try {

                $sql = "
                    SELECT
                        cl.id_label as id_label,
                        cl.nome as nome,
                        cl.referencial as referencial,
                        cl.grupo as grupo,
                        cl.descricao as descricao,
                        cl.disable as disabled,
                        cl.fk_label as fk_label,
                        cl.display_order,
                        LEFT(l.label_key, 2) as sigla,
                        l.label_value as parent_name
                    FROM
                        tbl_child_labels cl
                    INNER JOIN
                        tbl_labels l ON cl.fk_label = l.id
                    WHERE
                        cl.disable = 0
                        AND cl.referencial IS NOT NULL
                    ORDER BY
                        COALESCE(l.display_order, l.id) ASC, 
                        COALESCE(cl.display_order, cl.referencial) ASC;
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $childLabels = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return $childLabels;
            } catch (PDOException $error) {
                return $childLabels = "Erro: " . $error->getMessage();
            }
        }

        public function cacheLabelsRedis() {
            try {
                $this->delLabelsRedis();
        
                $expiracao = 60 * 60 * 24 * 7;
        
                $labels = $this->selectLabels();
                $childLabels = $this->getChildLabels();
        
                if (!is_array($labels) || !is_array($childLabels)) {
                    return false;
                }
        
                foreach ($labels as $label) {
                    $key = 'l:' . $label['label_id'];
                    $this->redis->setex($key, $expiracao, json_encode($label));
                }
        
                foreach ($childLabels as $child) {
                    $key = 'cl:' . $child['sigla'] . ':' . $child['referencial'];
                    $this->redis->setex($key, $expiracao, json_encode($child));
                }
        
                return true;
            } catch (Exception $e) {
                error_log("[Redis] Erro ao armazenar labels no cache: " . $e->getMessage());
                return false;
            }
        }


        public function getLabelsRedis() {
            try {
                $cursor = null;
                $pattern = 'l:*';
                $labels = [];
        
                do {
                    $keys = $this->redis->scan($cursor, $pattern, 100);
                    if (!empty($keys)) {
                        foreach ($keys as $key) {
                            $value = $this->redis->get($key);
                            if ($value) {
                                $labels[] = json_decode($value, true);
                            }
                        }
                    }
                } while ($cursor !== 0);
        
                // Ordenar pelos mesmos critérios do banco: display_order primeiro, depois label_id
                usort($labels, function ($a, $b) {
                    $orderA = $a['display_order'] ?? $a['label_id'];
                    $orderB = $b['display_order'] ?? $b['label_id'];
                    return $orderA <=> $orderB;
                });
        
                return $labels;
            } catch (Exception $e) {
                error_log("[Redis] Erro ao buscar todas as labels: " . $e->getMessage());
                return [];
            }
        }

        public function getChildLabelsPorSigla($sigla) {
            try {
                $cursor = null;
                $pattern = 'cl:' . $sigla . ':*';
                $resultados = [];
        
                do {
                    $keys = $this->redis->scan($cursor, $pattern, 100);
                    if (!empty($keys)) {
                        foreach ($keys as $key) {
                            $value = $this->redis->get($key);
                            if ($value) {
                                $resultados[] = json_decode($value, true);
                            }
                        }
                    }
                } while ($cursor !== 0);
        
                // Aplicar ordenação por display_order (mesmo critério usado no sistema)
                usort($resultados, function ($a, $b) {
                    $orderA = $a['display_order'] ?? $a['referencial'];
                    $orderB = $b['display_order'] ?? $b['referencial'];
                    return $orderA <=> $orderB;
                });
        
                return $resultados;
            } catch (Exception $e) {
                error_log("[Redis] Erro ao buscar childLabels por sigla: " . $e->getMessage());
                return [];
            }
        }

        public function getChildLabelsRedis() {
            try {
                $cursor = null;
                $pattern = 'cl:*';
                $labels = [];
        
                do {
                    $keys = $this->redis->scan($cursor, $pattern, 100);
                    if (!empty($keys)) {
                        foreach ($keys as $key) {
                            $value = $this->redis->get($key);
                            if ($value) {
                                $labels[] = json_decode($value, true);
                            }
                        }
                    }
                } while ($cursor !== 0);
        
                return $labels;
            } catch (Exception $e) {
                error_log("[Redis] Erro ao buscar todas as labels: " . $e->getMessage());
                return [];
            }
        }

        public function delLabelsRedis() {
            try {
                $deleteByPattern = function ($pattern) {
                    $cursor = null;
                    do {
                        $keys = $this->redis->scan($cursor, $pattern, 100);
                        if (!empty($keys)) {
                            $this->redis->del(...$keys);
                        }
                    } while ($cursor !== 0);
                };
        
                $deleteByPattern('l:*');
                $deleteByPattern('cl:*');
        
                return true;
            } catch (Exception $error) {
                error_log("[Redis] Erro ao deletar labels: " . $error->getMessage());
                return false;
            }
        }

        // CRUD Methods for Labels
        public function insertLabel($data) {
            try {
                $sql = "INSERT INTO tbl_labels (label_key, label_value, url, class, description, disable) 
                        VALUES (:label_key, :label_value, :url, :class, :description, :disable)";
                
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    'label_key' => $data['label_key'],
                    'label_value' => $data['label_value'],
                    'url' => $data['url'] ?? '',
                    'class' => $data['class'] ?? '',
                    'description' => $data['description'] ?? '',
                    'disable' => $data['disable'] ?? 0
                ]);

                if ($result) {
                    $this->cacheLabelsRedis(); // Atualiza o cache
                    return $this->db->lastInsertId();
                }
                return false;
            } catch (PDOException $error) {
                error_log("Erro ao inserir label: " . $error->getMessage());
                return false;
            }
        }

        public function updateLabel($id, $data) {
            try {
                $sql = "UPDATE tbl_labels SET 
                        label_key = :label_key,
                        label_value = :label_value,
                        url = :url,
                        class = :class,
                        description = :description,
                        disable = :disable,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id";
                
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    'id' => $id,
                    'label_key' => $data['label_key'],
                    'label_value' => $data['label_value'],
                    'url' => $data['url'] ?? '',
                    'class' => $data['class'] ?? '',
                    'description' => $data['description'] ?? '',
                    'disable' => $data['disable'] ?? 0
                ]);

                if ($result) {
                    $this->cacheLabelsRedis(); // Atualiza o cache
                }
                return $result;
            } catch (PDOException $error) {
                error_log("Erro ao atualizar label: " . $error->getMessage());
                return false;
            }
        }

        public function deleteLabel($id) {
            try {
                // Primeiro deleta child labels relacionadas
                $sql1 = "DELETE FROM tbl_child_labels WHERE fk_label = :id";
                $stmt1 = $this->db->prepare($sql1);
                $stmt1->execute(['id' => $id]);

                // Depois deleta a label principal
                $sql2 = "DELETE FROM tbl_labels WHERE id = :id";
                $stmt2 = $this->db->prepare($sql2);
                $result = $stmt2->execute(['id' => $id]);

                if ($result) {
                    $this->cacheLabelsRedis(); // Atualiza o cache
                }
                return $result;
            } catch (PDOException $error) {
                error_log("Erro ao deletar label: " . $error->getMessage());
                return false;
            }
        }

        public function getLabelById($id) {
            try {
                $sql = "SELECT * FROM tbl_labels WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['id' => $id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $error) {
                error_log("Erro ao buscar label: " . $error->getMessage());
                return false;
            }
        }

        // CRUD Methods for Child Labels
        public function insertChildLabel($data) {
            try {
                $sql = "INSERT INTO tbl_child_labels (nome, referencial, grupo, descricao, disable, fk_label) 
                        VALUES (:nome, :referencial, :grupo, :descricao, :disable, :fk_label)";
                
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    'nome' => $data['nome'],
                    'referencial' => $data['referencial'],
                    'grupo' => $data['grupo'] ?? '',
                    'descricao' => $data['descricao'] ?? '',
                    'disable' => $data['disable'] ?? 0,
                    'fk_label' => $data['fk_label']
                ]);

                if ($result) {
                    $this->cacheLabelsRedis(); // Atualiza o cache
                    return $this->db->lastInsertId();
                }
                return false;
            } catch (PDOException $error) {
                error_log("Erro ao inserir child label: " . $error->getMessage());
                return false;
            }
        }

        public function updateChildLabel($id, $data) {
            try {
                $sql = "UPDATE tbl_child_labels SET 
                        nome = :nome,
                        referencial = :referencial,
                        grupo = :grupo,
                        descricao = :descricao,
                        disable = :disable,
                        fk_label = :fk_label
                        WHERE id_label = :id";
                
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    'id' => $id,
                    'nome' => $data['nome'],
                    'referencial' => $data['referencial'],
                    'grupo' => $data['grupo'] ?? '',
                    'descricao' => $data['descricao'] ?? '',
                    'disable' => $data['disable'] ?? 0,
                    'fk_label' => $data['fk_label']
                ]);

                if ($result) {
                    $this->cacheLabelsRedis(); // Atualiza o cache
                }
                return $result;
            } catch (PDOException $error) {
                error_log("Erro ao atualizar child label: " . $error->getMessage());
                return false;
            }
        }

        public function deleteChildLabel($id) {
            try {
                $sql = "DELETE FROM tbl_child_labels WHERE id_label = :id";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute(['id' => $id]);

                if ($result) {
                    $this->cacheLabelsRedis(); // Atualiza o cache
                }
                return $result;
            } catch (PDOException $error) {
                error_log("Erro ao deletar child label: " . $error->getMessage());
                return false;
            }
        }

        public function getChildLabelById($id) {
            try {
                $sql = "SELECT cl.*, l.label_value as parent_name 
                        FROM tbl_child_labels cl
                        LEFT JOIN tbl_labels l ON cl.fk_label = l.id
                        WHERE cl.id_label = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['id' => $id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $error) {
                error_log("Erro ao buscar child label: " . $error->getMessage());
                return false;
            }
        }

        public function getChildLabelsByParent($parent_id) {
            try {
                $sql = "SELECT * FROM tbl_child_labels 
                        WHERE fk_label = :parent_id 
                        ORDER BY COALESCE(display_order, referencial) ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['parent_id' => $parent_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $error) {
                error_log("Erro ao buscar child labels: " . $error->getMessage());
                return [];
            }
        }

        public function updateLabelsOrder($orders) {
            try {
                // NOTA: Esta funcionalidade requer a coluna display_order na tabela tbl_labels
                // Execute o script script_sql_add_display_order_column.sql para adicionar a coluna
                
                // Verificar se a coluna display_order existe
                $checkSql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_NAME = 'tbl_labels' 
                           AND COLUMN_NAME = 'display_order'";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute();
                $columnExists = $checkStmt->fetchColumn();
                
                if (!$columnExists) {
                    error_log("Coluna display_order não existe. Execute o script SQL fornecido.");
                    return false;
                }
                
                $this->db->beginTransaction();
                
                foreach ($orders as $order) {
                    $sql = "UPDATE tbl_labels SET display_order = :order WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        'order' => $order['order'],
                        'id' => $order['id']
                    ]);
                }
                
                $this->db->commit();
                $this->cacheLabelsRedis(); // Atualiza o cache
                return true;
            } catch (PDOException $error) {
                $this->db->rollBack();
                error_log("Erro ao atualizar ordem das labels: " . $error->getMessage());
                return false;
            }
        }

        /**
         * Atualiza a ordem das sub-labels dentro de uma label pai específica
         */
        public function updateChildLabelsOrder($parentId, $orders) {
            try {
                // Verificar se a coluna display_order existe na tabela tbl_child_labels
                $checkSql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_NAME = 'tbl_child_labels' 
                           AND COLUMN_NAME = 'display_order'";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute();
                $columnExists = $checkStmt->fetchColumn();
                
                if (!$columnExists) {
                    error_log("Coluna display_order não existe na tabela tbl_child_labels. Execute o script SQL fornecido.");
                    return false;
                }
                
                $this->db->beginTransaction();
                
                foreach ($orders as $order) {
                    $sql = "UPDATE tbl_child_labels 
                            SET display_order = :order 
                            WHERE id_label = :id AND fk_label = :parent_id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        'order' => $order['order'],
                        'id' => $order['id'],
                        'parent_id' => $parentId
                    ]);
                }
                
                $this->db->commit();
                $this->cacheLabelsRedis(); // Atualiza o cache
                return true;
            } catch (PDOException $error) {
                $this->db->rollBack();
                error_log("Erro ao atualizar ordem das child labels: " . $error->getMessage());
                return false;
            }
        }

        /**
         * Atualiza a ordem das sub-labels de forma simplificada (sem agrupamento por pai)
         */
        public function updateChildLabelsOrderSimple($orders) {
            try {
                // Verificar se a coluna display_order existe na tabela tbl_child_labels
                $checkSql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_NAME = 'tbl_child_labels' 
                           AND COLUMN_NAME = 'display_order'";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute();
                $columnExists = $checkStmt->fetchColumn();
                
                if (!$columnExists) {
                    error_log("Coluna display_order não existe na tabela tbl_child_labels. Execute o script SQL fornecido.");
                    return false;
                }
                
                $this->db->beginTransaction();
                
                foreach ($orders as $order) {
                    $sql = "UPDATE tbl_child_labels 
                            SET display_order = :order 
                            WHERE id_label = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        'order' => $order['order'],
                        'id' => $order['id']
                    ]);
                }
                
                $this->db->commit();
                $this->cacheLabelsRedis(); // Atualiza o cache
                return true;
            } catch (PDOException $error) {
                $this->db->rollBack();
                error_log("Erro ao atualizar ordem simples das child labels: " . $error->getMessage());
                return false;
            }
        }

        /**
         * Atualiza a ordem das sub-labels agrupadas por label pai (cada grupo começa do 1)
         */
        public function updateChildLabelsOrderGrouped($groupedOrders) {
            try {
                // Verificar se a coluna display_order existe na tabela tbl_child_labels
                $checkSql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_NAME = 'tbl_child_labels' 
                           AND COLUMN_NAME = 'display_order'";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute();
                $columnExists = $checkStmt->fetchColumn();
                
                if (!$columnExists) {
                    error_log("Coluna display_order não existe na tabela tbl_child_labels. Execute o script SQL fornecido.");
                    return false;
                }
                
                $this->db->beginTransaction();
                
                // Processar cada grupo de sub-labels por label pai
                foreach ($groupedOrders as $parentId => $orders) {
                    foreach ($orders as $order) {
                        $sql = "UPDATE tbl_child_labels 
                                SET display_order = :order 
                                WHERE id_label = :id AND fk_label = :parent_id";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                            'order' => $order['relativePosition'],
                            'id' => $order['id'],
                            'parent_id' => $parentId
                        ]);
                    }
                }
                
                $this->db->commit();
                $this->cacheLabelsRedis(); // Atualiza o cache
                return true;
            } catch (PDOException $error) {
                $this->db->rollBack();
                error_log("Erro ao atualizar ordem agrupada das child labels: " . $error->getMessage());
                return false;
            }
        }

        /**
         * Busca todas as labels e sub-labels organizadas hierarquicamente
         */
        public function getLabelsHierarchy() {
            try {
                // Buscar labels principais ordenadas
                $labelsData = $this->selectLabels();
                if (is_string($labelsData)) {
                    return $labelsData;
                }

                $hierarchy = [];
                
                foreach ($labelsData as $label) {
                    $labelId = $label['label_id'];
                    
                    // Buscar sub-labels desta label
                    $childLabels = $this->getChildLabelsByParent($labelId);
                    
                    $hierarchy[] = [
                        'label' => $label,
                        'children' => $childLabels
                    ];
                }

                return $hierarchy;
            } catch (Exception $error) {
                error_log("Erro ao buscar hierarquia de labels: " . $error->getMessage());
                return "Erro: " . $error->getMessage();
            }
        }

        /**
         * Busca sub-labels por label pai com informações da label pai
         */
        public function getChildLabelsWithParentInfo($parent_id = null) {
            try {
                $sql = "
                    SELECT
                        cl.id_label,
                        cl.nome,
                        cl.referencial,
                        cl.grupo,
                        cl.descricao,
                        cl.disable,
                        cl.fk_label,
                        cl.display_order,
                        l.label_value as parent_name,
                        l.label_key as parent_key,
                        LEFT(l.label_key, 2) as sigla
                    FROM
                        tbl_child_labels cl
                    INNER JOIN
                        tbl_labels l ON cl.fk_label = l.id
                ";
                
                $params = [];
                if ($parent_id) {
                    $sql .= " WHERE cl.fk_label = :parent_id";
                    $params['parent_id'] = $parent_id;
                }
                
                $sql .= " ORDER BY COALESCE(l.display_order, l.id) ASC, 
                                   COALESCE(cl.display_order, cl.referencial) ASC";

                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $error) {
                error_log("Erro ao buscar child labels com info do pai: " . $error->getMessage());
                return [];
            }
        }
    }
