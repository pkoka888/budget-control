# CSS Implementation Guide - How to Use Enhanced Styles

## Quick Start

Your Budget Control app now has a complete, modern CSS design system ready to use. Here's how to implement it in your PHP views.

---

## 1️⃣ Basic Page Layout

```html
<?php
// budgets/list.php

// Typical page structure with modern styling
?>

<!-- Main container with responsive padding -->
<div class="flex-1 flex flex-col overflow-hidden">
  <!-- Top section with header -->
  <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

      <!-- Page Title -->
      <div class="mb-8">
        <h1>Budgets</h1>
        <p class="text-slate-gray-600 mt-2">
          Manage and track your spending limits
        </p>
      </div>

      <!-- Flash Message (if exists) -->
      <?php if (isset($flash) && $flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> animate-slide-in-down mb-6">
          <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <!-- Action Button -->
      <div class="mb-6">
        <a href="/budgets/create" class="btn btn-primary">
          Create New Budget
        </a>
      </div>

      <!-- Content Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($budgets as $budget): ?>
          <!-- Card Example Below -->
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>
```

---

## 2️⃣ Using Cards

### Basic Card
```html
<div class="card">
  <h3>Card Title</h3>
  <p>Content goes here...</p>
</div>
```

### Card with Structure
```html
<div class="card">
  <!-- Header Section -->
  <div class="card-header">
    <h3>Budget Details</h3>
    <p class="text-slate-gray-500 text-sm">Monthly limit tracking</p>
  </div>

  <!-- Body Section -->
  <div class="card-body">
    <div class="mb-4">
      <p class="text-slate-gray-600 text-sm">Spent</p>
      <p class="text-2xl font-bold text-google-blue-600">
        $<?php echo number_format($budget['spent'], 2); ?>
      </p>
    </div>
    <div class="mb-4">
      <p class="text-slate-gray-600 text-sm">Remaining</p>
      <p class="text-2xl font-bold text-google-green-600">
        $<?php echo number_format($budget['remaining'], 2); ?>
      </p>
    </div>
  </div>

  <!-- Footer Section with Actions -->
  <div class="card-footer">
    <div class="flex gap-2">
      <a href="/budgets/<?php echo $budget['id']; ?>/edit" class="btn btn-ghost btn-sm">
        Edit
      </a>
      <button onclick="deleteBudget(<?php echo $budget['id']; ?>)" class="btn btn-danger btn-sm">
        Delete
      </button>
    </div>
  </div>
</div>
```

---

## 3️⃣ Using Forms

### Complete Form Example
```html
<div class="card max-w-2xl">
  <div class="card-header">
    <h2>Create New Budget</h2>
  </div>

  <form method="POST" action="/budgets" class="card-body">
    <!-- Text Input -->
    <div class="form-group">
      <label class="form-label form-label-required">
        Budget Name
      </label>
      <input
        type="text"
        name="name"
        class="form-input"
        placeholder="e.g., Monthly Groceries"
        required
      >
      <span class="form-help">
        Give this budget a descriptive name
      </span>
    </div>

    <!-- Select Input -->
    <div class="form-group">
      <label class="form-label form-label-required">
        Category
      </label>
      <select name="category_id" class="form-select" required>
        <option value="">Choose a category...</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?php echo $category['id']; ?>">
            <?php echo htmlspecialchars($category['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Number Input -->
    <div class="form-group">
      <label class="form-label form-label-required">
        Monthly Limit
      </label>
      <input
        type="number"
        name="limit"
        class="form-input"
        placeholder="1000.00"
        step="0.01"
        min="0"
        required
      >
      <span class="form-help">
        Maximum amount you want to spend this month
      </span>
    </div>

    <!-- Textarea -->
    <div class="form-group">
      <label class="form-label">
        Notes
      </label>
      <textarea
        name="notes"
        class="form-textarea"
        placeholder="Add any notes about this budget..."
      ></textarea>
    </div>

    <!-- Buttons -->
    <div class="card-footer">
      <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">
          Create Budget
        </button>
        <a href="/budgets" class="btn btn-secondary">
          Cancel
        </a>
      </div>
    </div>
  </form>
</div>
```

---

## 4️⃣ Using Buttons

### Button Variants
```html
<!-- Primary Action (Create, Submit, Save) -->
<button class="btn btn-primary">
  Create Budget
</button>

<!-- Secondary Action (Cancel, Reset) -->
<button class="btn btn-secondary">
  Cancel
</button>

<!-- Success Action (Confirm, Approve) -->
<button class="btn btn-success">
  Confirm
</button>

<!-- Danger Action (Delete, Remove) -->
<button class="btn btn-danger">
  Delete
</button>

<!-- Warning Action (Caution, Review) -->
<button class="btn btn-warning">
  Review
</button>

<!-- Ghost Button (Link-style) -->
<button class="btn btn-ghost">
  Learn More
</button>
```

### Button Sizes
```html
<!-- Small -->
<button class="btn btn-primary btn-sm">Small Button</button>

<!-- Medium (default) -->
<button class="btn btn-primary">Medium Button</button>

<!-- Large -->
<button class="btn btn-primary btn-lg">Large Button</button>
```

---

## 5️⃣ Using Alerts

### Success Alert
```html
<?php if ($success): ?>
  <div class="alert alert-success animate-slide-in-down">
    ✓ <?php echo htmlspecialchars($message); ?>
  </div>
<?php endif; ?>
```

### Error Alert
```html
<?php if ($errors): ?>
  <div class="alert alert-error animate-slide-in-down">
    ✗ Please fix the following errors:
    <ul class="mt-2 ml-4 list-disc">
      <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
```

### Warning Alert
```html
<div class="alert alert-warning">
  ⚠ You're approaching your budget limit
</div>
```

### Info Alert
```html
<div class="alert alert-info">
  ℹ New budgeting features are now available
</div>
```

---

## 6️⃣ Using Badges

```html
<!-- Status Badges -->
<span class="badge badge-success">
  Active
</span>

<span class="badge badge-warning">
  Review
</span>

<span class="badge badge-danger">
  Over Budget
</span>

<!-- In Card Example -->
<div class="card">
  <div class="flex justify-between items-start mb-4">
    <h3>Monthly Budget</h3>
    <span class="badge badge-primary">
      In Progress
    </span>
  </div>
  <!-- Content -->
</div>
```

---

## 7️⃣ Using Tables

```html
<div class="card overflow-x-auto">
  <table class="table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Description</th>
        <th>Category</th>
        <th>Amount</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($transactions as $transaction): ?>
        <tr>
          <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
          <td><?php echo htmlspecialchars($transaction['description']); ?></td>
          <td>
            <span class="badge badge-secondary">
              <?php echo htmlspecialchars($transaction['category']); ?>
            </span>
          </td>
          <td class="font-semibold">
            <?php echo $transaction['type'] === 'expense' ? '-' : '+'; ?>
            $<?php echo number_format($transaction['amount'], 2); ?>
          </td>
          <td>
            <button class="btn btn-ghost btn-sm">
              Edit
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
```

---

## 8️⃣ Form Validation States

### Success State
```html
<div class="form-group">
  <label class="form-label">Email</label>
  <input
    type="email"
    class="form-input is-success"
    value="user@example.com"
  >
  <span class="form-help">
    ✓ Email is valid
  </span>
</div>
```

### Error State
```html
<div class="form-group">
  <label class="form-label">Email</label>
  <input
    type="email"
    class="form-input is-error"
    value="invalid"
  >
  <span class="form-error">
    Email format is invalid
  </span>
</div>
```

---

## 9️⃣ Color Usage Guide

### Text Colors
```html
<!-- Primary text (default) -->
<p class="text-slate-gray-900">Dark text</p>

<!-- Secondary text -->
<p class="text-slate-gray-600">Secondary text</p>

<!-- Tertiary text -->
<p class="text-slate-gray-500">Tertiary text</p>

<!-- Google Blue (links, important) -->
<p class="text-google-blue-600">Important text</p>

<!-- Google Green (success) -->
<p class="text-google-green-600">Success message</p>

<!-- Google Red (error) -->
<p class="text-google-red-600">Error message</p>
```

### Background Colors
```html
<!-- Light backgrounds -->
<div class="bg-slate-gray-50 p-4">Light grey background</div>

<!-- Blue accent -->
<div class="bg-google-blue-50 p-4">Light blue background</div>

<!-- Green accent -->
<div class="bg-google-green-50 p-4">Light green background</div>

<!-- Red accent -->
<div class="bg-google-red-50 p-4">Light red background</div>
```

---

## 🔟 Responsive Design Examples

### Grid Layout
```html
<!-- 1 column on mobile, 2 on tablet, 3 on desktop -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
  <div class="card">Card 1</div>
  <div class="card">Card 2</div>
  <div class="card">Card 3</div>
</div>
```

### Flex Layout
```html
<!-- Stack on mobile, side-by-side on desktop -->
<div class="flex flex-col lg:flex-row gap-4">
  <div class="flex-1 card">Sidebar</div>
  <div class="flex-2 card">Main content</div>
</div>
```

### Responsive Padding
```html
<!-- Smaller padding on mobile, larger on desktop -->
<div class="p-4 sm:p-6 lg:p-8">
  Content with responsive padding
</div>
```

---

## 1️⃣1️⃣ Common Patterns

### Loading State
```html
<button class="btn btn-primary" id="submitBtn" disabled>
  <span class="loading-spinner mr-2"></span>
  Processing...
</button>
```

### Empty State
```html
<?php if (empty($items)): ?>
  <div class="text-center py-12">
    <div class="text-6xl mb-4">📭</div>
    <h3 class="text-xl font-semibold mb-2">No budgets yet</h3>
    <p class="text-slate-gray-600 mb-6">
      Create your first budget to get started
    </p>
    <a href="/budgets/create" class="btn btn-primary">
      Create Budget
    </a>
  </div>
<?php endif; ?>
```

### Confirmation Dialog
```html
<div id="confirmDialog" class="hidden">
  <div class="modal-overlay">
    <div class="modal-content">
      <h3>Confirm Deletion</h3>
      <p>Are you sure you want to delete this budget?</p>
      <div class="card-footer">
        <button class="btn btn-danger">Delete</button>
        <button class="btn btn-secondary">Cancel</button>
      </div>
    </div>
  </div>
</div>
```

---

## 🎨 Color Quick Reference

```
Blue    → Primary actions, links (#2196F3)
Red     → Errors, danger (#F44336)
Yellow  → Warnings, caution (#FFEB3B)
Green   → Success, completion (#4CAF50)
Grey    → Text, backgrounds (#F1F5F9-#0F172A)
```

---

## ✅ Implementation Checklist

When creating/updating a page:

- [ ] Use `.card` for content sections
- [ ] Use `.btn btn-primary` for main actions
- [ ] Use `.form-group` for form sections
- [ ] Include labels for all inputs
- [ ] Use `.alert alert-*` for feedback
- [ ] Test on mobile (responsive)
- [ ] Check text contrast
- [ ] Include error messages
- [ ] Test keyboard navigation
- [ ] Use appropriate color meanings

---

## 🚀 Getting Started

1. **Open a view file** (`views/budgets/list.php`)
2. **Replace generic styling** with new component classes
3. **Use `.card`, `.btn btn-primary`, `.form-group`**
4. **Test responsive design** (resize browser)
5. **Verify colors and contrast**

---

## 📚 Full Documentation

See `CSS_DESIGN_GUIDE.md` for:
- Complete component library
- Color palette details
- Accessibility standards
- Customization options
- Best practices

---

**Version**: 1.0
**Last Updated**: November 10, 2025
