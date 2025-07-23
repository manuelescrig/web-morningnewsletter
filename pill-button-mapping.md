# Pill Button CSS Class Mapping Guide

## Button Types to New Classes

### Primary Action Buttons (Blue Background)
- **Old**: `bg-primary hover-bg-primary-dark text-white btn-pill`
- **New**: `pill-primary`

### Secondary Action Buttons (White/Gray Background)
- **Old**: `border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 btn-pill`
- **New**: `pill-secondary`

### Tertiary Action Buttons (Light Blue Background)
- **Old**: `bg-E9F5FF text-092F64 border-93BFEF btn-pill`
- **New**: `pill-tertiary`

### Danger Action Buttons (Red Border)
- **Old**: `border border-red-300 text-red-700 hover:bg-red-50 btn-pill`
- **New**: `pill-danger`

### Success Action Buttons (Green)
- **Old**: `bg-green-500 hover:bg-green-600 text-white btn-pill`
- **New**: `pill-success`

## Status Badge Pills

### Success Badge
- **Old**: `bg-green-100 text-green-800 rounded-full px-2.5 py-0.5`
- **New**: `pill-badge pill-badge-success`

### Warning Badge
- **Old**: `bg-yellow-100 text-yellow-800 rounded-full px-2.5 py-0.5`
- **New**: `pill-badge pill-badge-warning`

### Danger Badge
- **Old**: `bg-red-100 text-red-800 rounded-full px-2.5 py-0.5`
- **New**: `pill-badge pill-badge-danger`

### Info Badge
- **Old**: `bg-primary-lightest text-primary-dark rounded-full px-2.5 py-0.5`
- **New**: `pill-badge pill-badge-info`

### Gray Badge
- **Old**: `bg-gray-100 text-gray-800 rounded-full px-2.5 py-0.5`
- **New**: `pill-badge pill-badge-gray`

## Implementation Notes
1. Remove all Tailwind utility classes for styling
2. Replace with appropriate pill-* class
3. Keep only structural classes (flex, inline-flex, items-center, etc.)
4. Custom.css allows easy customization of all properties