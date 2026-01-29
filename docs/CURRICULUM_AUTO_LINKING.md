# Curriculum Auto-Linking Feature

## Overview

This feature automatically links schedules to curriculum subjects to enable **Block Sectioning Conflict Detection**. Without curriculum links, the system cannot accurately determine which schedules are for the same year level, which would cause false positive conflicts between different year levels using the same section names.

## Why It's Important

In block sectioning, students in the same year level and section take ALL subjects together. If two different subjects are scheduled for the same section at the same time, students cannot attend both classes.

**Example Conflict:**
- **ITS 307 Section B** (Year 3): Monday-Tuesday-Thursday-Friday, 05:30 PM - 07:00 PM
- **ITS 310 Section B** (Year 3): Monday-Tuesday, 05:30 PM - 07:00 PM

These conflict because:
- Both are Year 3 subjects
- Both are Section B
- They share overlapping days (Monday & Tuesday)
- They have the same time slot

Students in Year 3 Section B cannot attend both classes simultaneously.

## How It Works

### Automatic Linking

The system automatically links schedules to curriculum when:

1. **Creating new schedules** - The curriculum link is established automatically during schedule creation
2. **Updating schedules** - The curriculum link is refreshed when editing schedules
3. **Manual command** - You can run a command to link all existing schedules

### Linking Criteria

A schedule is linked to a curriculum subject when ALL of these match:
- The subject
- The semester (1st Semester or 2nd Semester)
- The department

## Commands

### Auto-Link All Schedules

Link all schedules that don't have curriculum data:

```bash
php bin/console app:auto-link-curriculum
```

**Options:**
- `--dry-run` - Preview what would be linked without saving changes
- `--all` - Process all schedules, including those already linked

**Example:**
```bash
# Preview changes
php bin/console app:auto-link-curriculum --dry-run

# Actually apply the links
php bin/console app:auto-link-curriculum
```

### Scan for Block Sectioning Conflicts

Check all active schedules for block sectioning conflicts:

```bash
php bin/console app:scan-block-section-conflicts
```

This will show you:
- Which schedules have conflicts
- The year level and section involved
- The conflicting time slots and rooms

## Services

### CurriculumLinkingService

The `CurriculumLinkingService` handles automatic linking:

```php
use App\Service\CurriculumLinkingService;

// In your controller or service
public function __construct(
    CurriculumLinkingService $curriculumLinkingService
) {
    $this->curriculumLinkingService = $curriculumLinkingService;
}

// Auto-link a single schedule
$this->curriculumLinkingService->autoLinkCurriculum($schedule);

// Auto-link multiple schedules
$stats = $this->curriculumLinkingService->autoLinkMultiple($schedules);
// Returns: ['linked' => count, 'already_linked' => count, 'failed' => count]
```

### ScheduleConflictDetector

The conflict detector now properly checks block sectioning conflicts when curriculum data is available:

```php
use App\Service\ScheduleConflictDetector;

// Detect all conflicts
$conflicts = $conflictDetector->detectConflicts($schedule, $excludeSelf = false);

// Filter for block sectioning conflicts
$blockConflicts = array_filter($conflicts, function($conflict) {
    return $conflict['type'] === 'block_sectioning_conflict';
});
```

## Integration Points

### Schedule Creation (ScheduleController::new)

Automatically links curriculum when creating schedules:

```php
$schedule->setSubject($subject);
$schedule->setSemester($semester);
// ... set other properties ...

// Auto-link curriculum for block sectioning conflict detection
$this->curriculumLinkingService->autoLinkCurriculum($schedule);
```

### Schedule Updates (ScheduleController::edit)

Refreshes curriculum link when editing:

```php
// After updating schedule properties
$this->curriculumLinkingService->autoLinkCurriculum($schedule);
```

### Conflict Checking (ScheduleController::checkConflict)

The AJAX conflict checker also auto-links curriculum for real-time validation.

## Troubleshooting

### Schedule Not Linking

If a schedule cannot be linked to curriculum, check:

1. **Subject exists in curriculum** - The subject must be defined in a curriculum
2. **Semester matches** - Use exact values: "1st Semester" or "2nd Semester"
3. **Department is set** - The subject must belong to a department
4. **Curriculum term exists** - The curriculum must have a term for that semester

### False Positives (No Conflict Detected)

If two schedules should conflict but don't:

1. **Check curriculum links** - Run the auto-link command
2. **Verify year levels** - Both schedules must have curriculum data with year levels
3. **Check sections** - Ensure both schedules have the same section name
4. **Verify overlap** - Check that days and times actually overlap

### General Education Courses

GE (General Education) courses may not link automatically because they're not tied to specific department curricula. This is expected behavior - these courses typically don't participate in block sectioning.

## Statistics

After running `app:auto-link-curriculum`, you'll see:
- ✅ **Successfully Linked** - Schedules that were linked to curriculum
- ✔️ **Already Linked** - Schedules that already had curriculum links
- ❌ **No Match Found** - Schedules that couldn't be linked (usually GE courses)

## Best Practices

1. **Run auto-link after bulk imports** - If you import schedules from external sources
2. **Verify curriculum data** - Ensure your curriculum is up-to-date before creating schedules
3. **Check conflicts regularly** - Run the conflict scanner periodically to catch issues
4. **Use consistent naming** - Keep section names consistent (A, B, C, etc.)
5. **Maintain department assignments** - Ensure all subjects have proper department associations

## Technical Details

### Database Schema

The `schedules` table has a `curriculum_subject_id` column that links to `curriculum_subjects`:

```sql
curriculum_subject_id BIGINT UNSIGNED NULL
```

### Logging

The service logs all auto-linking activities:
- INFO: Successful links (with year level)
- NOTICE: No match found (with details)
- WARNING: Missing required data

Check your logs for detailed information about linking operations.
