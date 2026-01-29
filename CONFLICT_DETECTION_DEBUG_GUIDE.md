# Block Sectioning Conflict Detection - Debug Guide

## What We Fixed

### Backend (src/Controller/ScheduleController.php)
✅ Added comprehensive logging in `getExistingSections()` endpoint:
- Logs total number of schedules fetched
- Logs each schedule with Subject ID, Section, Year Level, and Semester
- Logs when each schedule is added to the map with its year level
- All logs prefixed with `[ExistingSections]` for easy filtering

### Frontend (templates/admin/schedule/new_v2.html.twig)
✅ Added detailed logging throughout conflict detection:
- When subject is selected, logs the fetch process
- Logs all received schedule data from backend
- Logs year level mapping process (which subjects map to which year levels)
- Logs the conflict checking loop (how many schedules being checked)
- Logs each block sectioning comparison:
  - Which subjects are being compared
  - Their year levels
  - Their day patterns
  - Their time ranges
  - Whether days overlap
  - Whether times overlap
  - Final conflict result

## How to Test

### Step 1: Open Browser Console
1. Open the schedule creation page in your browser
2. Press F12 to open Developer Tools
3. Click on the "Console" tab
4. Keep it open during the entire process

### Step 2: Select Subject
When you select a subject (e.g., ITS 310), you should see console output like:
```
=== FETCHING EXISTING SECTIONS ===
Subject ID: 52
Fetching from URL: /admin/schedule/existing-sections/52
Response status: 200
=== RECEIVED DATA FROM BACKEND ===
Raw data: {sections: [...], schedules: {...}, count: X, success: true}
Number of schedules: X
```

### Step 3: Check Year Level Mapping
After data loads, you should see:
```
Schedule 51_A_2nd Semester_3: {section: "A", semester: "2nd Semester", ...}
  Subject ID: 51, Year Level: 3
  → Mapped subject 51 to year level 3

Schedule 52_A_2nd Semester_3: {section: "A", semester: "2nd Semester", ...}
  Subject ID: 52, Year Level: 3
  → Mapped subject 52 to year level 3

=== YEAR LEVEL MAPPING COMPLETE ===
Subject Year Levels: {51: 3, 52: 3, ...}
Current Subject ID: 52
Current Subject Year Level: 3
```

**🔍 CRITICAL CHECK:** Look for "Current Subject Year Level" - if it shows `undefined`, the year level is not being stored correctly!

### Step 4: Fill in Schedule Form
Fill in:
- Section: A
- Room: Any room
- Day Pattern: M-T-TH-F
- Start Time: 7:00
- End Time: 8:30
- Semester: 2nd Semester
- Academic Year: (current year)

### Step 5: Watch Conflict Detection
As soon as you fill in all fields, you should see:
```
=== Checking Section 1 ===
Subject: ITS 310 (ID: 52)
Section: A Room: XX, Room Name
Day: M-T-TH-F Time: 07:00 - 08:30
Semester: 2nd Semester Academic Year: 2024-2025
Subject Year Level: 3

--- Checking X existing schedules for conflicts ---

Block sectioning check: ITS 308 Year 3 Section A vs ITS 310 Year 3 Section A
  Days: T-TH vs M-T-TH-F
  Times: 07:00-08:30 vs 07:00-08:30
  Same year (3) - Days overlap: ???, Times overlap: ???

  daysOverlap("M-T-TH-F", "T-TH"):
    days1: [M, T, TH, F]
    days2: [T, TH]
    overlap: true/false

  ❌ CONFLICT: Same section, same year, overlapping schedule
  OR
  ✓ No conflict: Different days or times
```

## What to Look For

### ✅ SUCCESS INDICATORS:
1. "Current Subject Year Level: 3" (or other number, not undefined)
2. "daysOverlap" shows `overlap: true` for M-T-TH-F vs T-TH
3. "❌ CONFLICT: Same section, same year, overlapping schedule" appears
4. Red badge shows "❌ 1 Conflict"

### ❌ FAILURE INDICATORS:
1. "Current Subject Year Level: undefined" - Year level not being loaded
2. "daysOverlap" shows `overlap: false` when it should be true
3. "✓ No conflict: Different year levels" when both are same year
4. Green badge shows "✅ No Conflict" when there should be conflict

## Backend Logs

Check your PHP error log (usually in `var/log/dev.log` or Apache/Nginx error log):

```
[ExistingSections] Total schedules fetched: 15
[ExistingSections] Subject 51, Section A, Year Level: 3, Semester: 2nd Semester
[ExistingSections] Added to map: 51_A_2nd Semester_3 -> Year Level: 3
[ExistingSections] Subject 52, Section A, Year Level: 3, Semester: 2nd Semester
[ExistingSections] Added to map: 52_A_2nd Semester_3 -> Year Level: 3
```

**🔍 CRITICAL CHECK:** If "Year Level: NULL" appears, the database isn't returning year level data!

## Possible Issues & Solutions

### Issue 1: Year Level is NULL in backend
**Symptom:** Backend log shows "Year Level: NULL"
**Cause:** Subject not linked to curriculum
**Solution:** Check if the subject has a curriculum_subject record

### Issue 2: Year Level undefined in frontend
**Symptom:** "Current Subject Year Level: undefined"
**Cause:** Year level not in response data OR mapping logic failing
**Solution:** Check the "Raw data" log - does it contain yearLevel field?

### Issue 3: Days don't overlap when they should
**Symptom:** "overlap: false" for M-T-TH-F vs T-TH
**Cause:** Day pattern split logic may be broken
**Solution:** Check the `days1` and `days2` arrays in console - are they correct?

### Issue 4: Conflict not showing even when all conditions met
**Symptom:** Year levels match, days overlap, times overlap, but no conflict
**Cause:** Check if `hasConflict` variable is being set
**Solution:** Look for the final log "Section 1 final result: hasConflict=???"

## Expected Behavior

### Scenario: ITS 308 (Year 3, Section A, T-TH, 7:00-8:30) ALREADY EXISTS

Creating ITS 310 (Year 3, Section A, M-T-TH-F, 7:00-8:30):
- ✅ Should show CONFLICT (both have T and TH, same year, same section, same time)

Creating ITS 310 (Year 3, Section A, M-W-F, 7:00-8:30):
- ✅ Should show NO CONFLICT (no overlapping days)

Creating ITS 310 (Year 3, Section A, T-TH, 9:00-10:30):
- ✅ Should show NO CONFLICT (different times)

Creating ITS 310 (Year 2, Section A, T-TH, 7:00-8:30):
- ✅ Should show NO CONFLICT (different year levels)

Creating ITS 310 (Year 3, Section B, T-TH, 7:00-8:30):
- ✅ Should show NO CONFLICT (different sections)

## Next Steps

1. **Test the current setup** - Follow the test steps above
2. **Copy all console output** - Save the entire console log
3. **Copy backend logs** - Check `var/log/dev.log` or your PHP error log
4. **Report findings** - Share what you see in the console and logs

This will help us identify exactly where the conflict detection is failing!
