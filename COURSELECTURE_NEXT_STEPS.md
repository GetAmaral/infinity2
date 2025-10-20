# CourseLecture Entity - Next Steps

**Date**: 2025-10-19
**Status**: Code Complete - Ready for Migration

---

## Quick Start - Execute These Commands

### 1. Verify PHP Syntax (DONE ✅)
```bash
php -l /home/user/inf/app/src/Entity/CourseLecture.php
php -l /home/user/inf/app/src/Repository/CourseLectureRepository.php
php -l /home/user/inf/app/src/Entity/StudentLecture.php
```
**Result**: All files have no syntax errors

---

### 2. Generate Database Migration (REQUIRED)
```bash
cd /home/user/inf
docker-compose exec app php bin/console make:migration --no-interaction
```

**What This Creates**:
- New migration file in `app/migrations/`
- SQL statements for all 25 new columns
- SQL statements for 5 new indexes

**Expected Output**:
```
Success!
Next: Review the new migration "app/migrations/VersionXXXXXXXXXXXX.php"
Then: Run the migration with php bin/console doctrine:migrations:migrate
```

---

### 3. Review & Enhance Migration (RECOMMENDED)

Open the generated migration file and add data migration:

```php
public function up(Schema $schema): void
{
    // Auto-generated ALTER TABLE statements will be here

    // ADD THIS: Copy length_seconds to duration_seconds
    $this->addSql('UPDATE course_lecture SET duration_seconds = COALESCE(length_seconds, 0)');

    // ADD THIS: Set organization from course module
    $this->addSql('
        UPDATE course_lecture cl
        SET organization_id = (
            SELECT c.organization_id
            FROM course_module cm
            JOIN course c ON cm.course_id = c.id
            WHERE cm.id = cl.course_module_id
        )
    ');

    // ADD THIS: Make organization_id NOT NULL after data migration
    $this->addSql('ALTER TABLE course_lecture ALTER COLUMN organization_id SET NOT NULL');
}
```

---

### 4. Run Migration (REQUIRED)
```bash
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

**Expected Output**:
```
Migrating up to VersionXXXXXXXXXXXX
  ++ migrating VersionXXXXXXXXXXXX
  ++ migrated (took XX ms)
```

---

### 5. Verify Migration Success
```bash
# Check database schema
docker-compose exec database psql -U app -d app_db -c "\d course_lecture"

# Verify indexes
docker-compose exec database psql -U app -d app_db -c "
  SELECT indexname, indexdef
  FROM pg_indexes
  WHERE tablename = 'course_lecture'
  ORDER BY indexname;
"

# Verify data migration
docker-compose exec database psql -U app -d app_db -c "
  SELECT
    id,
    name,
    duration_seconds,
    length_seconds,
    active,
    published,
    free,
    organization_id IS NOT NULL as has_org
  FROM course_lecture
  LIMIT 5;
"
```

---

### 6. Clear Cache (REQUIRED)
```bash
docker-compose exec app php bin/console cache:clear
docker-compose exec app php bin/console cache:warmup
```

---

### 7. Test API Endpoints

#### Test Admin Endpoint
```bash
curl -k https://localhost/api/admin/course-lectures \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  | jq .
```

**Expected**: JSON array with enhanced lecture data including:
- videoUrl, videoType, durationSeconds
- active, published, free
- viewCount, rating, organizationId

#### Test Free Lectures Endpoint
```bash
curl -k https://localhost/api/lectures/free \
  -H "Authorization: Bearer YOUR_USER_TOKEN" \
  | jq .
```

**Expected**: JSON array of only free, published, active lectures

#### Test Single Lecture
```bash
curl -k https://localhost/api/course-lectures/{LECTURE_ID} \
  -H "Authorization: Bearer YOUR_TOKEN" \
  | jq .
```

---

### 8. Update Existing Code (OPTIONAL)

Search for deprecated methods and update:

```bash
# Find usage of deprecated methods
cd /home/user/inf/app
grep -r "getLengthSeconds" src/ templates/
grep -r "setLengthSeconds" src/ templates/
grep -r "getLengthFormatted" src/ templates/

# Update to new methods:
# getLengthSeconds() → getDurationSeconds()
# setLengthSeconds() → setDurationSeconds()
# getLengthFormatted() → getDurationFormatted()
```

**Note**: Not urgent - deprecated methods still work

---

### 9. Update Forms (OPTIONAL)

Add new fields to `/home/user/inf/app/src/Form/CourseLectureFormType.php`:

```php
->add('active', CheckboxType::class, [
    'label' => 'Active',
    'required' => false,
])
->add('published', CheckboxType::class, [
    'label' => 'Published',
    'required' => false,
])
->add('free', CheckboxType::class, [
    'label' => 'Free Preview',
    'required' => false,
])
->add('videoUrl', UrlType::class, [
    'label' => 'Video URL',
    'required' => false,
])
->add('videoType', ChoiceType::class, [
    'label' => 'Video Type',
    'choices' => [
        'Upload' => 'upload',
        'YouTube' => 'youtube',
        'Vimeo' => 'vimeo',
        'S3' => 's3',
        'URL' => 'url',
    ],
])
->add('difficultyLevel', ChoiceType::class, [
    'label' => 'Difficulty',
    'choices' => [
        'Beginner' => 'beginner',
        'Intermediate' => 'intermediate',
        'Advanced' => 'advanced',
    ],
])
```

---

### 10. Write Tests (RECOMMENDED)

Create `/home/user/inf/app/tests/Entity/CourseLectureTest.php`:

```php
<?php

namespace App\Tests\Entity;

use App\Entity\CourseLecture;
use PHPUnit\Framework\TestCase;

class CourseLectureTest extends TestCase
{
    public function testGetVideoUrlWithDirectUrl(): void
    {
        $lecture = new CourseLecture();
        $lecture->setVideoUrl('https://example.com/video.mp4');

        $this->assertEquals('https://example.com/video.mp4', $lecture->getVideoUrl());
    }

    public function testGetVideoUrlWithUploadedFile(): void
    {
        $lecture = new CourseLecture();
        $lecture->setVideoPath('2024/10/video123.mp4');

        $this->assertEquals('/uploads/lectures/2024/10/video123.mp4', $lecture->getVideoUrl());
    }

    public function testDurationBackwardCompatibility(): void
    {
        $lecture = new CourseLecture();
        $lecture->setDurationSeconds(300);

        // Old method should return same value
        $this->assertEquals(300, $lecture->getLengthSeconds());

        // Setting via old method should work
        $lecture->setLengthSeconds(600);
        $this->assertEquals(600, $lecture->getDurationSeconds());
    }

    public function testMicrolearningDetection(): void
    {
        $lecture = new CourseLecture();

        $lecture->setDurationSeconds(180); // 3 minutes
        $this->assertTrue($lecture->isMicrolearning());

        $lecture->setDurationSeconds(600); // 10 minutes
        $this->assertFalse($lecture->isMicrolearning());
    }

    public function testPublishWorkflow(): void
    {
        $lecture = new CourseLecture();
        $this->assertFalse($lecture->isPublished());
        $this->assertNull($lecture->getPublishedAt());

        $lecture->publish();
        $this->assertTrue($lecture->isPublished());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lecture->getPublishedAt());

        $lecture->unpublish();
        $this->assertFalse($lecture->isPublished());
    }

    public function testRatingCalculation(): void
    {
        $lecture = new CourseLecture();
        $this->assertNull($lecture->getRating());
        $this->assertEquals(0, $lecture->getRatingCount());

        $lecture->addRating(4.0);
        $this->assertEquals(4.0, $lecture->getRating());
        $this->assertEquals(1, $lecture->getRatingCount());

        $lecture->addRating(5.0);
        $this->assertEquals(4.5, $lecture->getRating());
        $this->assertEquals(2, $lecture->getRatingCount());
    }
}
```

Run tests:
```bash
docker-compose exec app php bin/phpunit tests/Entity/CourseLectureTest.php
```

---

## Troubleshooting

### Issue: Migration fails with "column already exists"
**Solution**: Drop and recreate database (development only)
```bash
docker-compose exec app php bin/console doctrine:database:drop --force
docker-compose exec app php bin/console doctrine:database:create
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

### Issue: Organization_id is NULL
**Solution**: Run manual data migration
```bash
docker-compose exec database psql -U app -d app_db -c "
  UPDATE course_lecture cl
  SET organization_id = (
    SELECT c.organization_id
    FROM course_module cm
    JOIN course c ON cm.course_id = c.id
    WHERE cm.id = cl.course_module_id
  )
  WHERE organization_id IS NULL;
"
```

### Issue: API returns 500 error
**Solution**: Check logs and clear cache
```bash
docker-compose exec app tail -f var/log/app.log
docker-compose exec app php bin/console cache:clear
```

### Issue: Indexes not created
**Solution**: Manually create indexes
```bash
docker-compose exec database psql -U app -d app_db -c "
  CREATE INDEX idx_lecture_module_order ON course_lecture(course_module_id, view_order);
  CREATE INDEX idx_lecture_published ON course_lecture(published, active);
  CREATE INDEX idx_lecture_free ON course_lecture(free);
  CREATE INDEX idx_lecture_status ON course_lecture(processing_status);
  CREATE INDEX idx_lecture_organization ON course_lecture(organization_id);
"
```

---

## Performance Verification

### Test Query Speed (Before & After Indexes)

```sql
-- Test 1: Get lectures by module (should use idx_lecture_module_order)
EXPLAIN ANALYZE
SELECT * FROM course_lecture
WHERE course_module_id = 'xxx'
ORDER BY view_order ASC;

-- Test 2: Get published lectures (should use idx_lecture_published)
EXPLAIN ANALYZE
SELECT * FROM course_lecture
WHERE published = true AND active = true;

-- Test 3: Get free lectures (should use idx_lecture_free)
EXPLAIN ANALYZE
SELECT * FROM course_lecture
WHERE free = true;
```

**Expected Results**:
- Index Scan (not Seq Scan)
- Execution time < 5ms for tables with < 10k rows

---

## Rollback Plan (If Needed)

### 1. Rollback Migration
```bash
docker-compose exec app php bin/console doctrine:migrations:migrate prev --no-interaction
```

### 2. Verify Rollback
```bash
docker-compose exec database psql -U app -d app_db -c "\d course_lecture"
```

### 3. Clear Cache
```bash
docker-compose exec app php bin/console cache:clear
```

---

## Success Criteria

- ✅ Migration runs without errors
- ✅ All 25 new columns present in database
- ✅ All 5 indexes created
- ✅ Organization_id populated for all lectures
- ✅ Duration_seconds synced with length_seconds
- ✅ API endpoints return enhanced data
- ✅ No PHP errors in logs
- ✅ All tests pass (if written)

---

## Documentation References

1. **Full Analysis Report**: `/home/user/inf/course_lecture_entity_analysis_report.md`
   - Detailed property descriptions
   - 2025 LMS best practices
   - Performance benchmarks
   - Complete specifications

2. **Fixes Applied**: `/home/user/inf/COURSELECTURE_FIXES_APPLIED.md`
   - Summary of changes
   - Code examples
   - Backward compatibility notes

3. **This Guide**: `/home/user/inf/COURSELECTURE_NEXT_STEPS.md`
   - Step-by-step instructions
   - Commands to run
   - Troubleshooting

---

## Quick Command Reference

```bash
# Generate migration
docker-compose exec app php bin/console make:migration --no-interaction

# Run migration
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
docker-compose exec app php bin/console cache:clear

# Check schema
docker-compose exec database psql -U app -d app_db -c "\d course_lecture"

# Test API
curl -k https://localhost/api/admin/course-lectures -H "Authorization: Bearer TOKEN" | jq .

# Run tests
docker-compose exec app php bin/phpunit

# Check logs
docker-compose exec app tail -f var/log/app.log
```

---

## Timeline Estimate

- **Migration Generation**: 1 minute
- **Migration Review/Enhancement**: 5 minutes
- **Run Migration**: 1 minute
- **Verification**: 5 minutes
- **API Testing**: 5 minutes
- **Form Updates (optional)**: 30 minutes
- **Test Writing (optional)**: 1-2 hours

**Total (required steps only)**: ~15 minutes
**Total (with optional enhancements)**: 3-4 hours

---

## Support

If you encounter issues:

1. Check logs: `docker-compose exec app tail -f var/log/app.log`
2. Verify database: `docker-compose exec database psql -U app -d app_db`
3. Clear cache: `docker-compose exec app php bin/console cache:clear`
4. Review reports in `/home/user/inf/course_lecture_*.md`

---

**Ready to proceed!** Start with step 2 (Generate Migration).
