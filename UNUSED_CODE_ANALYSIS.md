# Unused Code and Files Analysis
Generated: January 7, 2026

## Summary
This document identifies potentially unused files, code, and assets in the Smart Scheduling System.

---

## 🔴 CONFIRMED UNUSED - SAFE TO DELETE

### 1. Templates
- **`templates/faculty/dashboard_new.html.twig`** (462 lines)
  - **Status**: NOT USED
  - **Reason**: No controller references this file. The system uses `dashboard.html.twig` instead.
  - **Action**: DELETE
  - **Impact**: None - this is an unused alternate dashboard version

### 2. JavaScript Controllers
- **`assets/controllers/hello_controller.js`** (16 lines)
  - **Status**: DEMO FILE - NOT USED
  - **Reason**: This is a Stimulus example controller. No HTML elements use `data-controller="hello"`.
  - **Action**: DELETE
  - **Impact**: None - this is just a demo file

### 3. Images (Unused)
- **`public/images/norsu-logo.png`**
  - **Status**: NOT USED (SVG version is used instead)
  - **Reason**: All templates reference `norsu-logo.svg`, not the PNG version
  - **Action**: DELETE or KEEP as backup
  - **Impact**: None if deleted (SVG version is sufficient)

- **`public/images/NORSU.png`**
  - **Status**: NOT USED
  - **Reason**: No references found in codebase
  - **Action**: DELETE or KEEP as backup
  - **Impact**: None if deleted

- **`public/images/cas-69480e3b2228b.png`**
  - **Status**: NOT USED
  - **Reason**: No references found in codebase (appears to be an uploaded college logo that's not being used)
  - **Action**: DELETE
  - **Impact**: None

- **`public/images/cba-69480e4ce0ee5.png`**
  - **Status**: NOT USED
  - **Reason**: No references found in codebase (appears to be an uploaded college logo that's not being used)
  - **Action**: DELETE
  - **Impact**: None

- **`public/images/loadform/bagong.png`**
  - **Status**: NOT USED
  - **Reason**: No references found in codebase
  - **Action**: DELETE
  - **Impact**: None

### 4. Templates (Used but consider review)
- **`templates/security/register.html.twig`**
  - **Status**: USED but consider if needed
  - **Reason**: Registration is active via `/register` route in SecurityController
  - **Action**: KEEP if self-registration is needed, otherwise disable the route
  - **Impact**: If deleted, users cannot self-register

---

## 🟡 UTILITY FILES - KEEP BUT RARELY USED

### 1. Console Commands
These are CLI tools for maintenance and should be kept:

- **`src/Command/ValidateSubjectDepartmentsCommand.php`**
  - **Status**: MAINTENANCE TOOL
  - **Command**: `php bin/console app:validate-subject-departments`
  - **Action**: KEEP
  - **Purpose**: Validates database integrity

- **`src/Command/ValidateDepartmentCollegesCommand.php`**
  - **Status**: MAINTENANCE TOOL
  - **Command**: `php bin/console app:validate-department-colleges`
  - **Action**: KEEP
  - **Purpose**: Validates database integrity

- **`src/Command/CurriculumReconcileCommand.php`**
  - **Status**: MAINTENANCE TOOL
  - **Command**: `php bin/console app:curriculum-reconcile`
  - **Action**: KEEP
  - **Purpose**: Database maintenance

- **`src/Command/SetSemesterCommand.php`**
  - **Status**: MAINTENANCE TOOL
  - **Action**: KEEP
  - **Purpose**: System configuration

- **`src/Command/SeedActivitiesCommand.php`**
  - **Status**: MAINTENANCE TOOL
  - **Action**: KEEP
  - **Purpose**: Database seeding

---

## 🟢 USED FILES - KEEP

### 1. PDF Services
All PDF services are actively used:
- `RoomSchedulePdfService.php` - Used by AdminController
- `FacultyReportPdfService.php` - Used by AdminController
- `RoomsReportPdfService.php` - Used by AdminController
- `TeachingLoadPdfService.php` - Uses loadform images

### 2. Images (In Use)
- `public/images/norsu-logo.svg` - Used throughout the application ✅
- `public/images/loadform/headers.png` - Used by TeachingLoadPdfService ✅
- `public/images/loadform/middlelogo1.png` - Used by TeachingLoadPdfService ✅
- `public/images/loadform/logo2.jpg` - Used by TeachingLoadPdfService ✅

### 3. Templates
- `templates/admin/users/base_list_alpine.html.twig` - Extended by 4 user list templates ✅
- All other templates are actively used by controllers ✅

### 4. Curriculum Template
- `public/curriculum_templates/NORSU_BSIT_Template.csv` - Keep for reference ✅

---

## 📊 Recommended Actions

### Immediate Deletions (Safe)
```bash
# Delete unused template
Remove-Item "templates/faculty/dashboard_new.html.twig"

# Delete demo controller
Remove-Item "assets/controllers/hello_controller.js"

# Delete unused images
Remove-Item "public/images/cas-69480e3b2228b.png"
Remove-Item "public/images/cba-69480e4ce0ee5.png"
Remove-Item "public/images/loadform/bagong.png"

# Optional: Delete redundant logo files (keep SVG)
Remove-Item "public/images/norsu-logo.png"
Remove-Item "public/images/NORSU.png"
```

### Review and Decide
1. **User Registration** - Decide if you want to keep or disable the `/register` route
2. **Console Commands** - These are maintenance tools, keep them for administrative tasks

---

## 💾 Storage Impact
- **Deletable Files**: ~7 files
- **Estimated Space Saved**: ~500KB - 2MB (mostly images)

---

## ⚠️ Migration Files
All migration files in `migrations/` folder should be KEPT. These are database version history and should never be deleted unless you're resetting the entire database.

---

## 🔍 Notes
- All Controllers are actively used
- All Services are actively used
- All main templates are actively used
- The base_list_alpine.html.twig template system is working correctly

## Next Steps
1. Review this report
2. Execute the deletion commands for confirmed unused files
3. Test the application after deletions
4. Commit changes with message: "chore: remove unused files and code"
