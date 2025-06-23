# NocoDB CRUD Integration for ProcessWire

A comprehensive ProcessWire module that provides complete CRUD (Create, Read, Update, Delete) operations for NocoDB integration, enabling seamless data synchronization between ProcessWire and NocoDB databases.

## Features

- **Complete CRUD Operations**: Create, read, update, and delete records in NocoDB
- **Bulk Operations**: Support for bulk create, update, and delete operations
- **Advanced Querying**: WHERE conditions, sorting, pagination, and search functionality
- **Automatic Form Processing**: Hooks for ProcessWire forms to automatically sync with NocoDB
- **Page Save Integration**: Automatic synchronization when ProcessWire pages are saved
- **Caching Support**: Built-in caching system for improved performance
- **CSV Export**: Export NocoDB data to CSV files
- **Schema Introspection**: Get table structure and column information
- **Upsert Operations**: Insert or update records based on unique fields
- **Error Handling**: Comprehensive error handling with detailed messages
- **Debug Mode**: Optional debug logging for troubleshooting

## Requirements

- ProcessWire 3.0.0 or higher
- PHP 7.4 or higher
- cURL extension enabled
- Valid NocoDB instance with API access

## Installation

1. **Download the module**
   ```bash
   git clone https://github.com/adhiarta/processwire-nocodb.git
   ```

2. **Copy to ProcessWire modules directory**
   ```
   /site/modules/NocoDB/
   ```

3. **Configure the module**
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

### Utility Functions

```php
// Get table schema
$schema = $noco->getTableSchema('users');

// Export to CSV
$filepath = $noco->exportToCsv('users', 'users_export.csv', [
    'status' => 'active'
]);
```

## Form Integration

You can automatically sync ProcessWire forms with NocoDB by adding special attributes:

```php
// Create form with NocoDB integration
$form = $modules->get('InputfieldForm');
$form->attr('data-nocodb-table', 'contact_submissions');
$form->attr('data-nocodb-action', 'create'); // create, update, or upsert

// Add form fields
$field = $modules->get('InputfieldText');
$field->name = 'name';
$field->label = 'Name';
$form->add($field);

$field = $modules->get('InputfieldEmail');
$field->name = 'email';
$field->label = 'Email';
$form->add($field);

// Process form
if($form->processInput($input->post)) {
    // Data automatically synced to NocoDB
    $result = json_decode($form->attr('data-nocodb-result'), true);
}
```

## Page Save Integration

Enable automatic synchronization when pages are saved:

1. Add fields to your template:
   - `nocodb_sync` (checkbox): Enable sync for this page
   - `nocodb_table` (text): Target table name (optional, uses template name if not specified)
   - `nocodb_id` (integer): Stores the NocoDB record ID

2. When pages with `nocodb_sync` enabled are saved, they automatically sync to NocoDB.

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

## Error Handling

The module provides comprehensive error handling:

```php
try {
    $result = $noco->create('users', $data);
} catch(WireException $e) {
    // Handle NocoDB-specific errors
    echo "Error: " . $e->getMessage();
}
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

### Core Methods

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

### Bulk Methods

| Method | Description |
|--------|-------------|
| `createBulk($table, $dataArray)` | Create multiple records |
| `updateBulk($table, $dataArray)` | Update multiple records |
| `deleteBulk($table, $ids)` | Delete multiple records by IDs |

### Utility Methods

| Method | Description |
|--------|-------------|
| `upsert($table, $data, $uniqueField)` | Insert or update record |
| `search($table, $field, $keyword, $limit)` | Search records |
| `count($table, $conditions)` | Count records |
| `getTableSchema($table)` | Get table structure |
| `exportToCsv($table, $filename, $conditions)` | Export to CSV |

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

## Changelog

### Version 1.0.0
- Initial release
- Complete CRUD operations
- Bulk operations support
- Form and page integration
- Caching system
- CSV export functionality
- Comprehensive error handling

---

**Made with ❤️ for the ProcessWire community**
