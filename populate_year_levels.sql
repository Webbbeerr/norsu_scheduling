-- Populate year_level for existing subjects based on subject code patterns
-- This assumes your subjects follow naming convention: ITS 1XX = Year 1, ITS 2XX = Year 2, etc.

-- Year 1 subjects (100-199)
UPDATE subjects 
SET year_level = 1, semester = 'First'
WHERE code LIKE '%1__' 
  AND year_level IS NULL
  AND code REGEXP '[A-Z]+ 1[0-9]{2}';

-- Year 2 subjects (200-299)
UPDATE subjects 
SET year_level = 2, semester = 'First'
WHERE code LIKE '%2__' 
  AND year_level IS NULL
  AND code REGEXP '[A-Z]+ 2[0-9]{2}';

-- Year 3 subjects (300-399)
UPDATE subjects 
SET year_level = 3, semester = 'Second'
WHERE code LIKE '%3__' 
  AND year_level IS NULL
  AND code REGEXP '[A-Z]+ 3[0-9]{2}';

-- Year 4 subjects (400-499)
UPDATE subjects 
SET year_level = 4, semester = 'Second'
WHERE code LIKE '%4__' 
  AND year_level IS NULL
  AND code REGEXP '[A-Z]+ 4[0-9]{2}';

-- Verify the results
SELECT 
    code,
    title,
    year_level,
    semester,
    department_id
FROM subjects
ORDER BY code;

-- Check subjects that still don't have year_level set
SELECT 
    code,
    title,
    department_id
FROM subjects
WHERE year_level IS NULL;
