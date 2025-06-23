## Advanced Error Handling

The module provides comprehensive error handling with different error types:

```php
try {
    $result = $noco->create('users', $data);
} catch(NocoDBConnectionException $e) {
    // Handle connection errors
    echo "Connection Error: " . $e->getMessage();
    // Maybe retry with exponential backoff
    $noco->retryWithBackoff(function() use ($data) {
        return $noco->create('users', $data);
    });
} catch(NocoDBValidationException $e) {
    // Handle validation errors
    $errors = $e->getValidationErrors();
    foreach($errors as $field => $error) {
        echo "Field {$field}: {$error}\n";
    }
} catch(NocoDBQuotaException $e) {
    // Handle quota/rate limit errors
    echo "Quota exceeded. Retry after: " . $e->getRetryAfter();
    sleep($e->getRetryAfter());
} catch(WireException $e) {
    // Handle general errors
    echo "Error: " . $e->getMessage();
}

// Custom error handlers
$noco->setErrorHandler('connection', function($error) {
    // Custom connection error handling
    $this->log->error("NocoDB connection failed: " . $error->getMessage());
    // Send notification to admin
    $this->mail->send('admin@site.com', 'NocoDB Error', $error->getMessage());
});
```# NocoDB CRUD Integration for ProcessWire

A comprehensive ProcessWire module that provides complete CRUD (Create, Read, Update, Delete) operations for NocoDB integration, enabling seamless data synchronization between ProcessWire and NocoDB databases.

## Features

### Core CRUD Operations
- **Complete CRUD Operations**: Create, read, update, and delete records in NocoDB
- **Bulk Operations**: Support for bulk create, update, and delete operations
- **Advanced Querying**: WHERE conditions, sorting, pagination, and search functionality
- **Upsert Operations**: Insert or update records based on unique fields

### Data Management & Export
- **CSV Export/Import**: Export NocoDB data to CSV and import CSV files
- **JSON Export/Import**: Export and import data in JSON format
- **Excel Integration**: Support for Excel file import/export (.xlsx, .xls)
- **Data Validation**: Built-in validation rules for data integrity
- **Data Transformation**: Transform data before saving to NocoDB

### ProcessWire Integration
- **Automatic Form Processing**: Hooks for ProcessWire forms to automatically sync with NocoDB
- **Page Save Integration**: Automatic synchronization when ProcessWire pages are saved
- **Field Mapping**: Map ProcessWire fields to NocoDB columns
- **Template Integration**: Automatic table creation based on ProcessWire templates
- **User Activity Tracking**: Track user actions and modifications

### Performance & Optimization
- **Caching Support**: Multi-layer caching system for improved performance
- **Connection Pooling**: Efficient connection management
- **Lazy Loading**: Load data on demand for better performance
- **Background Sync**: Asynchronous data synchronization
- **Rate Limiting**: Built-in rate limiting to prevent API overload

### Advanced Features
- **Schema Introspection**: Get table structure and column information
- **Database Migration**: Migrate data between NocoDB instances
- **Backup & Restore**: Create and restore database backups
- **Webhook Integration**: Support for NocoDB webhooks
- **Real-time Sync**: Real-time data synchronization using WebSockets
- **Multi-tenant Support**: Support for multiple NocoDB projects
- **API Versioning**: Support for different NocoDB API versions

### Security & Monitoring
- **Access Control**: Role-based access control for operations
- **Audit Logging**: Complete audit trail of all operations
- **Error Handling**: Comprehensive error handling with detailed messages
- **Debug Mode**: Optional debug logging for troubleshooting
- **Health Monitoring**: Monitor NocoDB connection health
- **Security Headers**: Additional security measures for API calls

## Requirements

- ProcessWire 3.0.0 or higher
- PHP 7.4 or higher
- cURL extension enabled
- Valid NocoDB instance with API access

## Installation

1. **Download the module**
   ```bash
   git clone https://github.com/your-username/processwire-nocodb.git
   ```

2. **Copy to ProcessWire modules directory**
   ```
   /site/modules/NocoDB/
   ```

3. **Install via ProcessWire Admin**
   - Go to Modules → Install
   - Find "NocoDB CRUD Integration"
   - Click Install

4. **Configure the module**
   - Go to Modules → Configure → NocoDB CRUD Integration
   - Enter your NocoDB API URL, token, and other settings

## Configuration

### Required Settings

- **API URL**: Your NocoDB instance URL (e.g., `https://your-nocodb.com`)
- **API Token**: Your NocoDB API authentication token

### Optional Settings

- **Project ID**: Specific NocoDB project ID (if needed)
- **Request Timeout**: HTTP request timeout in seconds (default: 30)
- **Debug Mode**: Enable debug logging
- **Cache Settings**: Enable caching for GET requests with configurable expiration

## Usage

### Basic CRUD Operations

```php
// Access the module
$noco = $modules->get('NocoDB');
// or use the wire variable
$noco = $noco;

// CREATE - Add new record
$result = $noco->create('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

// READ - Get all records with options
$users = $noco->read('users', [
    'limit' => 10,
    'offset' => 0,
    'sort' => 'name',
    'fields' => 'name,email'
]);

// READ - Get record by ID
$user = $noco->readById('users', 123);

// UPDATE - Update record by ID
$result = $noco->update('users', 123, [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

// DELETE - Delete record by ID
$result = $noco->delete('users', 123);
```

### Advanced Querying

```php
// Read with WHERE conditions
$activeUsers = $noco->readWhere('users', [
    'status' => 'active',
    'age' => ['gt', 18]  // age > 18
], 50, 0);

// Search records
$searchResults = $noco->search('users', 'name', 'John', 10);

// Count records
$count = $noco->count('users', ['status' => 'active']);

// Update records by condition
$result = $noco->updateWhere('users', 
    ['status' => 'inactive'], 
    ['status' => 'archived']
);

// Delete records by condition
$result = $noco->deleteWhere('users', ['status' => 'spam']);
```

### Bulk Operations

```php
// Bulk create
$users = [
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
    ['name' => 'User 3', 'email' => 'user3@example.com']
];
$result = $noco->createBulk('users', $users);

// Bulk update
$updates = [
    ['id' => 1, 'status' => 'active'],
    ['id' => 2, 'status' => 'inactive'],
    ['id' => 3, 'status' => 'pending']
];
$result = $noco->updateBulk('users', $updates);

// Bulk delete by IDs
$result = $noco->deleteBulk('users', [1, 2, 3, 4, 5]);
```

### Upsert Operations

```php
// Insert or update based on unique field
$result = $noco->upsert('users', [
    'email' => 'john@example.com',
    'name' => 'John Updated',
    'age' => 31
], 'email'); // Use email as unique identifier
```

### Data Import/Export

```php
// Import from CSV
$result = $noco->importFromCsv('users', '/path/to/users.csv', [
    'skip_header' => true,
    'validate' => true,
    'on_duplicate' => 'update' // skip, update, or error
]);

// Import from Excel
$result = $noco->importFromExcel('products', '/path/to/products.xlsx', [
    'sheet' => 'Products',
    'mapping' => [
        'Product Name' => 'name',
        'Price' => 'price',
        'Category' => 'category'
    ]
]);

// Export to Excel
$filepath = $noco->exportToExcel('users', 'users_export.xlsx', [
    'status' => 'active'
], [
    'include_headers' => true,
    'formatting' => true
]);

// Export to JSON
$filepath = $noco->exportToJson('users', 'users_backup.json');

// Backup entire table
$result = $noco->backupTable('users', [
    'include_schema' => true,
    'compress' => true
]);

// Restore from backup
$result = $noco->restoreTable('users', '/path/to/backup.json');
```

### Real-time Operations

```php
// Set up real-time sync
$noco->enableRealTimeSync('users', function($event, $data) {
    // Handle real-time updates
    switch($event) {
        case 'created':
            // Handle new record
            break;
        case 'updated':
            // Handle updated record
            break;
        case 'deleted':
            // Handle deleted record
            break;
    }
});

// WebSocket connection for live updates
$noco->connectWebSocket('users', [
    'onMessage' => function($message) {
        // Handle incoming messages
    },
    'onError' => function($error) {
        // Handle errors
    }
]);
```

### Advanced Data Operations

```php
// Data validation
$result = $noco->validateData('users', [
    'name' => 'John Doe',
    'email' => 'invalid-email', // This will fail validation
    'age' => 25
]);

// Data transformation
$result = $noco->transformAndCreate('users', [
    'full_name' => 'John Doe',
    'contact_email' => 'john@example.com'
], [
    'full_name' => 'name', // Transform full_name to name
    'contact_email' => 'email' // Transform contact_email to email
]);

// Batch processing with progress tracking
$noco->batchProcess('users', $largeDataArray, [
    'batch_size' => 100,
    'progress_callback' => function($processed, $total) {
        echo "Processed: {$processed}/{$total}\n";
    }
]);

// Aggregate operations
$stats = $noco->aggregate('sales', [
    'sum' => ['amount'],
    'avg' => ['rating'],
    'count' => ['*'],
    'group_by' => ['category']
]);
```

### Multi-tenant Operations

```php
// Switch between projects
$noco->setProject('project_1');
$users1 = $noco->read('users');

$noco->setProject('project_2');
$users2 = $noco->read('users');

// Sync data between projects
$result = $noco->syncBetweenProjects('users', 'project_1', 'project_2', [
    'conflict_resolution' => 'newer_wins'
]);
```

### Database Migration

```php
// Migrate table schema
$result = $noco->migrateSchema('users', 'target_nocodb_instance', [
    'create_if_not_exists' => true,
    'update_existing' => true
]);

// Migrate data with transformation
$result = $noco->migrateData('users', 'target_instance', [
    'batch_size' => 1000,
    'transform_callback' => function($record) {
        // Transform record before migration
        $record['migrated_at'] = date('Y-m-d H:i:s');
        return $record;
    }
]);
```

### Advanced Form Integration

```php
// Create form with advanced NocoDB integration
$form = $modules->get('InputfieldForm');
$form->attr('data-nocodb-table', 'contact_submissions');
$form->attr('data-nocodb-action', 'create');
$form->attr('data-nocodb-validate', 'true'); // Enable validation
$form->attr('data-nocodb-transform', 'true'); // Enable data transformation
$form->attr('data-nocodb-webhook', 'contact_webhook'); // Trigger webhook

// File upload integration
$field = $modules->get('InputfieldFile');
$field->name = 'attachments';
$field->attr('data-nocodb-upload', 'true'); // Auto-upload to NocoDB
$form->add($field);

// Real-time form validation
$form->attr('data-nocodb-realtime-validate', 'true');

// Process form with advanced features
if($form->processInput($input->post)) {
    $result = json_decode($form->attr('data-nocodb-result'), true);
    
    // Access additional metadata
    $uploadedFiles = $form->attr('data-nocodb-uploads');
    $validationErrors = $form->attr('data-nocodb-validation-errors');
    $webhookResponse = $form->attr('data-nocodb-webhook-response');
}
```

### Page Save Integration with Field Mapping

```php
// Advanced page save integration
// Add these fields to your template:
// - nocodb_sync (checkbox): Enable sync
// - nocodb_table (text): Target table 
// - nocodb_id (integer): Record ID
// - nocodb_mapping (textarea): JSON field mapping
// - nocodb_transform (textarea): Data transformation rules
// - nocodb_conditions (textarea): Sync conditions

// Example field mapping in nocodb_mapping field:
/*
{
    "title": "product_name",
    "body": "description", 
    "price": "price",
    "images": {
        "field": "image_urls",
        "transform": "array_to_string"
    }
}
*/

// The module will automatically handle the mapping and transformation
```

### Webhook Integration

```php
// Set up webhooks for table events
$noco->createWebhook('users', [
    'url' => 'https://your-site.com/nocodb-webhook',
    'events' => ['create', 'update', 'delete'],
    'filters' => ['status' => 'active'],
    'headers' => [
        'Authorization' => 'Bearer your-token',
        'Content-Type' => 'application/json'
    ]
]);

// Handle incoming webhooks
$noco->handleWebhook('users', function($event, $data) {
    switch($event) {
        case 'create':
            // Handle new record creation
            $page = new Page();
            $page->template = 'user';
            $page->title = $data['name'];
            $page->save();
            break;
            
        case 'update':
            // Handle record update
            $page = $pages->get("nocodb_id={$data['id']}");
            if($page->id) {
                $page->title = $data['name'];
                $page->save();
            }
            break;
            
        case 'delete':
            // Handle record deletion
            $page = $pages->get("nocodb_id={$data['id']}");
            if($page->id) {
                $page->delete();
            }
            break;
    }
});
```

## WHERE Clause Operators

The module supports various operators for WHERE conditions:

```php
$conditions = [
    'age' => ['gt', 18],        // age > 18
    'status' => ['eq', 'active'], // status = 'active'
    'name' => ['like', '%john%'], // name LIKE '%john%'
    'created_date' => ['gte', '2024-01-01'], // created_date >= '2024-01-01'
    'score' => ['lt', 100],     // score < 100
    'category' => ['in', 'tech,business,health'] // category IN (...)
];
```

Available operators:
- `eq` - equals
- `neq` - not equals
- `gt` - greater than
- `gte` - greater than or equal
- `lt` - less than
- `lte` - less than or equal
- `like` - LIKE pattern matching
- `in` - IN clause
- `is` - IS (for null values)
- `isnot` - IS NOT

### Monitoring & Analytics

```php
// Health check
$health = $noco->healthCheck();
/*
Returns:
{
    "status": "healthy",
    "response_time": 120,
    "api_version": "0.109.0",
    "database_status": "connected",
    "last_check": "2024-01-15 10:30:00"
}
*/

// Get usage statistics
$stats = $noco->getUsageStats('users', [
    'period' => '7d', // 1h, 24h, 7d, 30d
    'metrics' => ['reads', 'writes', 'deletes']
]);

// Performance monitoring
$noco->enablePerformanceMonitoring([
    'slow_query_threshold' => 1000, // ms
    'log_all_queries' => false,
    'alert_on_errors' => true
]);

// Get audit logs
$logs = $noco->getAuditLogs('users', [
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'user_id' => 123,
    'actions' => ['create', 'update', 'delete']
]);
```

### Security Features

```php
// Role-based access control
$noco->setUserRole('editor', [
    'tables' => ['posts', 'pages'],
    'permissions' => ['read', 'create', 'update'],
    'conditions' => ['author_id' => '$user_id'] // Row-level security
]);

// Check permissions before operations
if ($noco->hasPermission('users', 'create')) {
    $result = $noco->create('users', $data);
}

// Encrypt sensitive data
$noco->setEncryptionFields('users', ['ssn', 'credit_card']);

// Data masking for non-production environments
$noco->enableDataMasking([
    'email' => 'email_mask',
    'phone' => 'phone_mask',
    'name' => 'name_mask'
]);
```

### Queue & Background Processing

```php
// Queue operations for background processing
$noco->queueOperation('bulk_import', [
    'table' => 'users',
    'file' => '/path/to/large-file.csv',
    'options' => ['validate' => true]
]);

// Process queue
$noco->processQueue([
    'max_items' => 10,
    'timeout' => 300,
    'on_progress' => function($processed, $total) {
        echo "Queue progress: {$processed}/{$total}\n";
    }
]);

// Schedule recurring operations
$noco->scheduleOperation('daily_backup', [
    'frequency' => 'daily',
    'time' => '02:00',
    'operation' => 'backup',
    'tables' => ['users', 'orders']
]);
```

## Caching

Enable caching to improve performance for read operations:

```php
// Configure in module settings or programmatically
$noco->cache_enabled = true;
$noco->cache_expire = 300; // 5 minutes

// Cache is automatically used for read operations
$users = $noco->read('users'); // This will be cached
```

## API Reference

### Extended Core Methods

| Method | Description |
|--------|-------------|
| `create($table, $data)` | Create a new record |
| `read($table, $options)` | Read records with options |
| `readById($table, $id)` | Read record by ID |
| `readWhere($table, $conditions, $limit, $offset)` | Read with WHERE conditions |
| `update($table, $id, $data)` | Update record by ID |
| `updateWhere($table, $conditions, $data)` | Update records by conditions |
| `delete($table, $id)` | Delete record by ID |
| `deleteWhere($table, $conditions)` | Delete records by conditions |
| `validateData($table, $data)` | Validate data against table schema |
| `transformData($data, $mapping)` | Transform data using field mapping |

### Bulk & Batch Methods

| Method | Description |
|--------|-------------|
| `createBulk($table, $dataArray)` | Create multiple records |
| `updateBulk($table, $dataArray)` | Update multiple records |
| `deleteBulk($table, $ids)` | Delete multiple records by IDs |
| `batchProcess($table, $data, $options)` | Process large datasets in batches |
| `batchValidate($table, $dataArray)` | Validate multiple records |

### Import/Export Methods

| Method | Description |
|--------|-------------|
| `importFromCsv($table, $file, $options)` | Import data from CSV file |
| `importFromExcel($table, $file, $options)` | Import data from Excel file |
| `importFromJson($table, $file, $options)` | Import data from JSON file |
| `exportToCsv($table, $filename, $conditions)` | Export to CSV |
| `exportToExcel($table, $filename, $conditions, $options)` | Export to Excel |
| `exportToJson($table, $filename, $conditions)` | Export to JSON |

### Utility & Analysis Methods

| Method | Description |
|--------|-------------|
| `upsert($table, $data, $uniqueField)` | Insert or update record |
| `search($table, $field, $keyword, $limit)` | Search records |
| `count($table, $conditions)` | Count records |
| `aggregate($table, $operations)` | Perform aggregate operations |
| `getTableSchema($table)` | Get table structure |
| `getTableStats($table)` | Get table statistics |
| `analyzeTable($table)` | Analyze table performance |

### Backup & Migration Methods

| Method | Description |
|--------|-------------|
| `backupTable($table, $options)` | Backup table data and schema |
| `restoreTable($table, $backupFile)` | Restore table from backup |
| `migrateSchema($table, $target, $options)` | Migrate table schema |
| `migrateData($table, $target, $options)` | Migrate table data |
| `syncTables($sourceTable, $targetTable, $options)` | Sync data between tables |

### Real-time & WebSocket Methods

| Method | Description |
|--------|-------------|
| `enableRealTimeSync($table, $callback)` | Enable real-time synchronization |
| `connectWebSocket($table, $options)` | Connect to WebSocket for live updates |
| `subscribeToChanges($table, $callback)` | Subscribe to table changes |
| `broadcastChange($table, $event, $data)` | Broadcast changes to subscribers |

### Webhook & Integration Methods

| Method | Description |
|--------|-------------|
| `createWebhook($table, $config)` | Create webhook for table events |
| `updateWebhook($webhookId, $config)` | Update existing webhook |
| `deleteWebhook($webhookId)` | Delete webhook |
| `listWebhooks($table)` | List all webhooks for table |
| `handleWebhook($table, $callback)` | Handle incoming webhook |
| `testWebhook($webhookId)` | Test webhook configuration |

### Security & Access Control Methods

| Method | Description |
|--------|-------------|
| `setUserRole($role, $permissions)` | Set role-based permissions |
| `hasPermission($table, $operation)` | Check user permissions |
| `encryptField($table, $field, $value)` | Encrypt sensitive field data |
| `decryptField($table, $field, $value)` | Decrypt field data |
| `maskData($data, $rules)` | Apply data masking rules |

### Monitoring & Performance Methods

| Method | Description |
|--------|-------------|
| `healthCheck()` | Check NocoDB instance health |
| `getUsageStats($table, $options)` | Get usage statistics |
| `getAuditLogs($table, $options)` | Get audit trail |
| `enablePerformanceMonitoring($options)` | Enable performance tracking |
| `getPerformanceMetrics($period)` | Get performance metrics |
| `optimizeTable($table)` | Optimize table performance |

### Queue & Background Processing Methods

| Method | Description |
|--------|-------------|
| `queueOperation($name, $params)` | Queue operation for background processing |
| `processQueue($options)` | Process queued operations |
| `getQueueStatus()` | Get queue processing status |
| `scheduleOperation($name, $schedule)` | Schedule recurring operations |
| `cancelScheduledOperation($name)` | Cancel scheduled operation |

### Multi-tenant & Project Methods

| Method | Description |
|--------|-------------|
| `setProject($projectId)` | Switch to different project |
| `listProjects()` | List available projects |
| `createProject($config)` | Create new project |
| `syncBetweenProjects($table, $source, $target, $options)` | Sync between projects |
| `cloneProject($sourceId, $targetConfig)` | Clone entire project |

## Troubleshooting

### Common Issues

1. **Connection Errors**
   - Verify your NocoDB URL and API token
   - Check if cURL is enabled
   - Ensure your server can reach the NocoDB instance

2. **Authentication Errors**
   - Verify your API token is correct and has proper permissions
   - Check if the token has expired

3. **Table Not Found**
   - Ensure the table name exists in your NocoDB instance
   - Check case sensitivity of table names

4. **Performance Issues**
   - Enable caching for read operations
   - Use appropriate limits for large datasets
   - Consider using bulk operations for multiple records

### Debug Mode

Enable debug mode in module configuration to get detailed logging:

```php
// Enable in module config or:
$noco->debug = true;
```

This will log all HTTP requests and responses to ProcessWire's message system.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

## License

This module is released under the MIT License. See [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/your-username/processwire-nocodb/issues)
- **Documentation**: [ProcessWire Modules Directory](https://modules.processwire.com/)
- **ProcessWire Forum**: [Community Support](https://processwire.com/talk/)

## Changelog

### Version 1.0.0
- Initial release with comprehensive CRUD operations
- Bulk operations support
- Form and page integration
- Advanced caching system
- CSV export functionality
- Comprehensive error handling

### Version 1.1.0 (Enhanced Features)
- **Import/Export**: Excel and JSON import/export support
- **Real-time Sync**: WebSocket integration for live updates
- **Data Validation**: Built-in validation engine
- **Field Mapping**: Advanced field transformation
- **Webhook Integration**: Complete webhook support
- **Security**: Role-based access control and data encryption
- **Performance**: Connection pooling and lazy loading
- **Monitoring**: Health checks and audit logging
- **Background Processing**: Queue system for large operations
- **Multi-tenant**: Support for multiple NocoDB projects
- **Migration Tools**: Database migration utilities
- **Analytics**: Usage statistics and performance metrics

---

**Made with ❤️ for the ProcessWire community**
