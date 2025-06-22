<?php namespace ProcessWire;

/**
 * NocoDB CRUD Module for ProcessWire
 * 
 * Provides complete CRUD operations for NocoDB integration
 * 
 * ProcessWire 3.x
 * Copyright (C) 2024
 * Licensed under MIT License
 * 
 */

class NocoDB extends WireData implements Module, ConfigurableModule {

    /**
     * Module information
     */
    public static function getModuleInfo() {
        return array(
            'title' => 'NocoDB CRUD Integration',
            'summary' => 'Complete CRUD operations for NocoDB integration with ProcessWire',
            'version' => '1.0.0',
            'author' => 'ProcessWire Developer',
            'href' => 'https://github.com/your-username/processwire-nocodb',
            'icon' => 'database',
            'autoload' => true,
            'singular' => true,
            'requires' => 'ProcessWire>=3.0.0'
        );
    }

    /**
     * Default configuration
     */
    protected static $defaultConfig = array(
        'api_url' => '',
        'api_token' => '',
        'project_id' => '',
        'timeout' => 30,
        'debug' => false,
        'cache_enabled' => false,
        'cache_expire' => 300
    );

    /**
     * API configuration
     */
    protected $apiUrl;
    protected $apiToken;
    protected $projectId;
    protected $headers;
    protected $timeout;

    /**
     * Initialize the module
     */
    public function init() {
        $apiUrl = rtrim($apiUrl ?? '', '/');
        $this->apiToken = $this->api_token;
        $this->projectId = $this->project_id;
        $this->timeout = $this->timeout ?: 30;

        $this->headers = array(
            'Content-Type: application/json',
            'xc-token: ' . $this->apiToken
        );

        // Add API methods to ProcessWire's $noco variable
        $this->wire('noco', $this);
    }

    /**
     * Ready hook - called when ProcessWire is ready
     */
    public function ready() {
        // Add hooks for automatic form processing
        $this->addHookAfter('InputfieldForm::processInput', $this, 'hookFormProcessInput');
        
        // Add hooks for page save operations
        $this->addHookAfter('Pages::save', $this, 'hookPageSave');
    }

    // ========== CREATE OPERATIONS ==========

    /**
     * Create a new record
     */
    public function create($tableName, $data) {
        $url = $this->apiUrl . "/api/v1/db/data/noco/{$tableName}";
        return $this->makeRequest($url, 'POST', $data);
    }

    /**
     * Create multiple records
     */
    public function createBulk($tableName, $dataArray) {
        $url = $this->apiUrl . "/api/v1/db/data/noco/{$tableName}/bulk";
        return $this->makeRequest($url, 'POST', $dataArray);
    }

    // ========== READ OPERATIONS ==========

    /**
     * Read records with options
     */
    public function read($tableName, $options = array()) {
        $defaults = array(
            'limit' => 25,
            'offset' => 0,
            'sort' => null,
            'where' => null,
            'fields' => null
        );

        $options = array_merge($defaults, $options);
        $url = $this->apiUrl . "/api/v1/db/data/noco/{$tableName}";
        
        $params = array();
        if ($options['limit']) $params['limit'] = $options['limit'];
        if ($options['offset']) $params['offset'] = $options['offset'];
        if ($options['sort']) $params['sort'] = $options['sort'];
        if ($options['where']) $params['where'] = $options['where'];
        if ($options['fields']) $params['fields'] = $options['fields'];

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // Check cache first
        if ($this->cache_enabled) {
            $cacheKey = 'nocodb_' . md5($url);
            $cached = $this->wire('cache')->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $result = $this->makeRequest($url, 'GET');

        // Cache the result
        if ($this->cache_enabled && $result) {
            $this->wire('cache')->save($cacheKey, $result, $this->cache_expire);
        }

        return $result;
    }

    /**
     * Read record by ID
     */
    public function readById($tableName, $id) {
        $url = $this->apiUrl . "/api/v1/db/data/noco/{$tableName}/{$id}";
        return $this->makeRequest($url, 'GET');
    }

    /**
     * Read records with WHERE conditions
     */
    public function readWhere($tableName, $conditions, $limit = 25, $offset = 0) {
        $whereClause = $this->buildWhereClause($conditions);
        return $this->read($tableName, array(
            'where' => $whereClause,
            'limit' => $limit,
            'offset' => $offset
        ));
    }

    /**
     * Search records
     */
    public function search($tableName, $searchField, $keyword, $limit = 25) {
        $whereClause = "({$searchField},like,%{$keyword}%)";
        return $this->read($tableName, array(
            'where' => $whereClause,
            'limit' => $limit
        ));
    }

    /**
     * Count records
     */
    public function count($tableName, $conditions = null) {
        $url = $this->apiUrl . "/api/v1/db/data/noco/{$tableName}/count";
        
        if ($conditions) {
            $whereClause = $this->buildWhereClause($conditions);
            $url .= '?where=' . urlencode($whereClause);
        }

        return $this->makeRequest($url, 'GET');
    }

    // ========== UPDATE OPERATIONS ==========

    /**
     * Update record by ID
     */
    public function update($tableName, $id, $data) {
        $url = $this->apiUrl . "/api/v1/db/data/noco/{$tableName}/{$id}";
        
        // Clear cache for this table
        if ($this->cache_enabled) {
            $this->clearTableCache($tableName);
        }
        
        return $this->makeRequest($url, 'PATCH', $data);
    }

    /**
     * Update records by conditions
     */
    public function updateWhere($tableName, $conditions, $data) {
        $records = $this->readWhere($tableName, $conditions, 1000);
        
        if (!isset($records['list']) || empty($records['list'])) {
            return array('updated' => 0, 'message' => 'No records found');
        }

        $results = array();
        foreach ($records['list'] as $record) {
            $id = isset($record['Id']) ? $record['Id'] : $record['id'];
            if ($id) {
                $results[] = $this->update($tableName, $id, $data);
            }
        }

        return array(
            'updated' => count($results),
            'results' => $results
        );
    }

    /**
     * Update multiple records
     */
    public function updateBulk($tableName, $dataArray) {
        $url = $this->apiUrl . "/api/v1/db/data/noco/{$tableName}/bulk";
        
        if ($this->cache_enabled) {
            $this->clearTableCache($tableName);
        }
        
        return $this->makeRequest($url, 'PATCH', $dataArray);
    }

    // ========== DELETE OPERATIONS ==========

    /**
     * Delete record by ID
     */
    public function delete($tableName, $id) {
        $url = $this->apiUrl . "/api/v1/db/data/noco/{$tableName}/{$id}";
        
        if ($this->cache_enabled) {
            $this->clearTableCache($tableName);
        }
        
        return $this->makeRequest($url, 'DELETE');
    }

    /**
     * Delete records by conditions
     */
    public function deleteWhere($tableName, $conditions) {
        $records = $this->readWhere($tableName, $conditions, 1000);
        
        if (!isset($records['list']) || empty($records['list'])) {
            return array('deleted' => 0, 'message' => 'No records found');
        }

        $results = array();
        foreach ($records['list'] as $record) {
            $id = isset($record['Id']) ? $record['Id'] : $record['id'];
            if ($id) {
                $results[] = $this->delete($tableName, $id);
            }
        }

        return array(
            'deleted' => count($results),
            'results' => $results
        );
    }

    /**
     * Delete multiple records by IDs
     */
    public function deleteBulk($tableName, $ids) {
        $url = $this->apiUrl . "/api/v1/db/data/noco/{$tableName}/bulk";
        
        if ($this->cache_enabled) {
            $this->clearTableCache($tableName);
        }
        
        return $this->makeRequest($url, 'DELETE', array('ids' => $ids));
    }

    // ========== ADDITIONAL OPERATIONS ==========

    /**
     * Upsert operation
     */
    public function upsert($tableName, $data, $uniqueField = 'id') {
        if (isset($data[$uniqueField])) {
            $conditions = array($uniqueField => $data[$uniqueField]);
            $existing = $this->readWhere($tableName, $conditions, 1);
            
            if (isset($existing['list']) && !empty($existing['list'])) {
                $existingRecord = $existing['list'][0];
                $id = isset($existingRecord['Id']) ? $existingRecord['Id'] : $existingRecord['id'];
                return $this->update($tableName, $id, $data);
            }
        }
        
        return $this->create($tableName, $data);
    }

    /**
     * Get table schema
     */
    public function getTableSchema($tableName) {
        $url = $this->apiUrl . "/api/v1/db/meta/tables/{$tableName}/columns";
        return $this->makeRequest($url, 'GET');
    }

    /**
     * Export to CSV
     */
    public function exportToCsv($tableName, $filename = null, $conditions = null) {
        $data = $this->readWhere($tableName, $conditions, 10000);
        
        if (!isset($data['list']) || empty($data['list'])) {
            return false;
        }

        $filename = $filename ?: $tableName . '_export_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = $this->wire('config')->paths->assets . $filename;

        $file = fopen($filepath, 'w');
        
        // Write headers
        $headers = array_keys($data['list'][0]);
        fputcsv($file, $headers);
        
        // Write data
        foreach ($data['list'] as $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);
        return $filepath;
    }

    // ========== PROCESSWIRE HOOKS ==========

    /**
     * Hook for form processing
     */
    public function hookFormProcessInput(HookEvent $event) {
        $form = $event->object;
        
        if ($form->attr('data-nocodb-table')) {
            $tableName = $form->attr('data-nocodb-table');
            $action = $form->attr('data-nocodb-action') ?: 'create';
            
            $data = array();
            foreach ($form->getAll() as $field) {
                if ($field->attr('name') && $field->value) {
                    $data[$field->attr('name')] = $field->value;
                }
            }
            
            try {
                switch ($action) {
                    case 'create':
                        $result = $this->create($tableName, $data);
                        break;
                    case 'update':
                        $id = $form->attr('data-record-id');
                        $result = $this->update($tableName, $id, $data);
                        break;
                    case 'upsert':
                        $uniqueField = $form->attr('data-unique-field') ?: 'id';
                        $result = $this->upsert($tableName, $data, $uniqueField);
                        break;
                }
                
                $form->attr('data-nocodb-result', json_encode($result));
                
            } catch(\Exception $e) {
                $form->error($e->getMessage());
            }
        }
    }

    /**
     * Hook for page save
     */
    public function hookPageSave(HookEvent $event) {
        $page = $event->arguments[0];
        
        if ($page->hasField('nocodb_sync') && $page->nocodb_sync) {
            $tableName = $page->hasField('nocodb_table') ? $page->nocodb_table : $page->template->name;
            
            $data = array();
            foreach ($page->fields as $field) {
                if ($field->type instanceof FieldtypeText || 
                    $field->type instanceof FieldtypeTextarea ||
                    $field->type instanceof FieldtypeInteger ||
                    $field->type instanceof FieldtypeFloat) {
                    $data[$field->name] = $page->get($field->name);
                }
            }
            
            try {
                if ($page->hasField('nocodb_id') && $page->nocodb_id) {
                    $this->update($tableName, $page->nocodb_id, $data);
                } else {
                    $result = $this->create($tableName, $data);
                    if (isset($result['Id'])) {
                        $page->setAndSave('nocodb_id', $result['Id']);
                    }
                }
            } catch(\Exception $e) {
                if ($this->debug) {
                    $this->error("NocoDB sync failed: " . $e->getMessage());
                }
            }
        }
    }

    // ========== HELPER METHODS ==========

    /**
     * Build WHERE clause
     */
    protected function buildWhereClause($conditions) {
        if (empty($conditions)) return null;
        
        $clauses = array();
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $operator = $value[0];
                $val = $value[1];
                $clauses[] = "({$field},{$operator},{$val})";
            } else {
                $clauses[] = "({$field},eq,{$value})";
            }
        }
        
        return implode('~and', $clauses);
    }

    /**
     * Clear table cache
     */
    protected function clearTableCache($tableName) {
        $cache = $this->wire('cache');
        $cache->deleteFor($this->className, "table_{$tableName}_*");
    }

    /**
     * Make HTTP request
     */
    protected function makeRequest($url, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true
        ));
        
        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new WireException("cURL Error: " . $error);
        }
        
        if ($httpCode >= 400) {
            $errorResponse = json_decode($response, true);
            $errorMessage = isset($errorResponse['msg']) ? $errorResponse['msg'] : $response;
            throw new WireException("HTTP Error {$httpCode}: " . $errorMessage);
        }
        
        if ($this->debug) {
            $this->message("NocoDB Request: {$method} {$url} - Response: {$httpCode}");
        }
        
        return json_decode($response, true);
    }

    // ========== MODULE CONFIGURATION ==========

    /**
     * Configuration fields for the module
     */
    public static function getModuleConfigInputfields(array $data) {
        $data = array_merge(self::$defaultConfig, $data);
        $fields = new InputfieldWrapper();
        
        // API URL
        $field = wire('modules')->get('InputfieldURL');
        $field->name = 'api_url';
        $field->label = __('NocoDB API URL');
        $field->description = __('Full URL to your NocoDB instance (e.g. https://your-nocodb.com)');
        $field->value = $data['api_url'];
        $field->required = true;
        $fields->add($field);
        
        // API Token
        $field = wire('modules')->get('InputfieldText');
        $field->name = 'api_token';
        $field->label = __('API Token');
        $field->description = __('Your NocoDB API token');
        $field->value = $data['api_token'];
        $field->required = true;
        $field->attr('type', 'password');
        $fields->add($field);
        
        // Project ID
        $field = wire('modules')->get('InputfieldText');
        $field->name = 'project_id';
        $field->label = __('Project ID');
        $field->description = __('NocoDB Project ID (optional)');
        $field->value = $data['project_id'];
        $fields->add($field);
        
        // Timeout
        $field = wire('modules')->get('InputfieldInteger');
        $field->name = 'timeout';
        $field->label = __('Request Timeout');
        $field->description = __('HTTP request timeout in seconds');
        $field->value = $data['timeout'];
        $field->min = 5;
        $field->max = 300;
        $fields->add($field);
        
        // Debug
        $field = wire('modules')->get('InputfieldCheckbox');
        $field->name = 'debug';
        $field->label = __('Debug Mode');
        $field->description = __('Enable debug logging');
        $field->value = $data['debug'];
        $fields->add($field);
        
        // Cache
        $field = wire('modules')->get('InputfieldCheckbox');
        $field->name = 'cache_enabled';
        $field->label = __('Enable Caching');
        $field->description = __('Cache GET requests to improve performance');
        $field->value = $data['cache_enabled'];
        $fields->add($field);
        
        // Cache expire
        $field = wire('modules')->get('InputfieldInteger');
        $field->name = 'cache_expire';
        $field->label = __('Cache Expire Time');
        $field->description = __('Cache expiration time in seconds');
        $field->value = $data['cache_expire'];
        $field->showIf = 'cache_enabled=1';
        $fields->add($field);
        
        return $fields;
    }

    // ========== MAGIC METHODS ==========

    /**
     * Make the module callable
     */
    public function __invoke($method, ...$args) {
        if (method_exists($this, $method)) {
            return $this->$method(...$args);
        }
        throw new WireException("Method {$method} does not exist");
    }
}
