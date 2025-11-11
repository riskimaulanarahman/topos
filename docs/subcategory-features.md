# Subcategory Features Documentation

## Overview

Subcategory feature telah ditambahkan ke sistem untuk mendukung hierarchical categories. Fitur ini dirancang khusus untuk keperluan reporting di web interface, sementara mobile API tetap flat untuk menjaga backward compatibility.

## Database Changes

### Migration: `add_parent_id_to_categories_table`

-   **Column Added**: `parent_id` (BigInteger, nullable)
-   **Foreign Key**: References `categories.id` dengan `nullOnDelete()`
-   **Index**: Added index pada `parent_id` untuk performa query

### Impact

-   Existing categories otomatis menjadi root categories (parent_id = null)
-   New subcategories dapat memiliki parent_id pointing ke existing categories
-   Support unlimited nesting levels

## Model Updates

### Category Model Enhancements

**New Relationships:**

-   `parent()` - belongsTo parent category
-   `children()` - hasMany child categories
-   `descendants()` - recursive children with eager loading
-   `ancestors()` - recursive parent traversal

**New Methods:**

-   `getFullPathAttribute()` - Returns "Main > Sub > Sub-Sub" format
-   `isRoot()` - Check if category has no parent
-   `isLeaf()` - Check if category has no children
-   `isValidParent($parentId)` - Validate parent to prevent circular reference
-   `getAllDescendantIds()` - Get all descendant IDs efficiently

**New Scopes:**

-   `scopeRoot()` - Filter root categories only
-   `scopeByParent($parentId)` - Filter by parent

**Static Methods:**

-   `getTree($parentId, $excludeId)` - Get hierarchical tree structure
-   `getFlattenedList($parentId, $prefix, $excludeId)` - Get flat list with indentation

**Validation:**

-   Boot method untuk automatic validation saat save
-   Prevent circular references
-   Ensure parent belongs to same user/outlet

## Web Interface Changes

### Category Management (`/categories`)

**Index Page:**

-   **Tree View**: Default view dengan expandable/collapsible hierarchy
-   **Flat View**: Toggle untuk traditional table view
-   **Visual Indicators**: Badge untuk subcategories count dan products count
-   **Interactive**: Click to expand/collapse branches

**Create/Edit Forms:**

-   **Parent Dropdown**: Select parent category atau "Main Category"
-   **Validation**: Real-time validation untuk circular reference prevention
-   **Visual Feedback**: Current parent display di edit form

### Product Management

**Category Assignment:**

-   **Hierarchical Dropdown**: Shows categories dengan indentation (─ Main, ── Sub, etc.)
-   **Path Display**: Product list shows full category path (Main > Sub)
-   **Backward Compatible**: Existing products tetap berfungsi normal

### Tree View Features

-   **Expand/Collapse**: Interactive tree navigation
-   **Visual Hierarchy**: Clear parent-child relationships
-   **Performance**: Efficient loading dengan eager loading
-   **Responsive**: Mobile-friendly tree display

## API Compatibility

### Mobile API (Unchanged)

**Endpoints:**

-   `GET /api/categories` - Returns flat list (no hierarchy)
-   `POST /api/categories` - Create category (parent_id optional)
-   `PUT /api/categories/edit` - Update category (parent_id optional)
-   `DELETE /api/categories/{id}` - Delete category

**Response Format:**

```json
{
    "message": "Categories retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Beverages",
            "user_id": 1,
            "outlet_id": 1,
            "image": "beverages.jpg",
            "parent_id": 2, // Included but not exposed to mobile UI
            "created_at": "2025-01-01T00:00:00.000000Z",
            "updated_at": "2025-01-01T00:00:00.000000Z"
        }
    ]
}
```

**Key Points:**

-   Mobile API tetap flat - tidak ada hierarchy di response
-   `parent_id` disimpan di database tapi tidak digunakan mobile client
-   Backward compatibility 100% terjaga

## Validation Rules

### Circular Reference Prevention

**Model Level:**

-   Automatic validation saat save
-   Check if parent adalah descendant dari current category
-   Prevent infinite loops

**Controller Level:**

-   Form validation untuk parent selection
-   User/outlet ownership validation
-   Error messages yang user-friendly

### Business Rules

1. **Same User/Outlet**: Parent category harus milik user dan outlet yang sama
2. **No Self-Reference**: Category tidak bisa menjadi parent dari dirinya sendiri
3. **No Circular Reference**: Tidak bisa membuat loop A > B > C > A
4. **Soft Delete Handling**: Children dari deleted categories tetap visible

## Performance Considerations

### Database Optimization

-   **Index**: `parent_id` column di-index untuk fast lookups
-   **Eager Loading**: Tree queries menggunakan `with('descendants')`
-   **Caching**: Static caching untuk `hasProductCategoryColumn()`

### Query Efficiency

-   **Recursive CTE**: Available untuk complex hierarchy queries
-   **Batch Operations**: Efficient bulk operations
-   **Lazy Loading**: Children loaded hanya saat needed

## Usage Examples

### Creating Subcategories

```php
// Create main category
$food = Category::create([
    'name' => 'Food',
    'user_id' => 1,
    'outlet_id' => 1,
    'parent_id' => null
]);

// Create subcategory
$beverages = Category::create([
    'name' => 'Beverages',
    'user_id' => 1,
    'outlet_id' => 1,
    'parent_id' => $food->id
]);

// Create sub-subcategory
$coffee = Category::create([
    'name' => 'Coffee',
    'user_id' => 1,
    'outlet_id' => 1,
    'parent_id' => $beverages->id
]);
```

### Getting Category Path

```php
$category = Category::find(3);
echo $category->full_path; // "Food > Beverages > Coffee"
```

### Building Category Tree

```php
$tree = Category::getTree();
// Returns hierarchical structure for tree view
```

### Getting Flattened List

```php
$categories = Category::getFlattenedList();
// Returns: [
//     ['id' => 1, 'name' => 'Food', 'level' => 0],
//     ['id' => 2, 'name' => '─ Beverages', 'level' => 1],
//     ['id' => 3, 'name' => '── Coffee', 'level' => 2],
// ]
```

## Migration Strategy

### Existing Data

-   All existing categories menjadi root categories (parent_id = null)
-   No data loss selama migration
-   Gradual rollout possible

### Rollback Plan

-   Migration dapat di-rollback safely
-   Foreign key constraints ensure data integrity
-   Backup recommended sebelum migration

## Future Enhancements

### Potential Features

1. **Drag & Drop**: Visual category reorganization
2. **Bulk Operations**: Mass category updates
3. **Category Permissions**: Role-based access per category
4. **Analytics**: Category performance reporting
5. **Import/Export**: Category hierarchy management

### Reporting Integration

-   Hierarchical sales reports
-   Category performance comparison
-   Subcategory analysis dashboard

## Troubleshooting

### Common Issues

1. **Circular Reference Error**

    - Cause: Trying to set parent yang adalah descendant
    - Solution: Pilih parent yang valid

2. **Permission Denied**

    - Cause: Parent category milik user/outlet berbeda
    - Solution: Pilih parent yang sesuai

3. **Performance Issues**
    - Cause: Deep hierarchy tanpa proper indexing
    - Solution: Ensure migration dijalankan dan index ada

### Debug Information

Enable query logging untuk debugging:

```php
\DB::enableQueryLog();
// Run category operations
\Log::info(\DB::getQueryLog());
```

## Conclusion

Subcategory feature berhasil diimplementasi dengan:

-   ✅ Hierarchical categories untuk web reporting
-   ✅ Mobile API backward compatibility
-   ✅ Data integrity dan validation
-   ✅ Performance optimization
-   ✅ User-friendly interface

Fitur ini siap digunakan untuk analisa report yang lebih detail sambil menjaga stabilitas mobile application.
