#!/bin/bash

# ============================================================================
# COMPANY ENTITY OPTIMIZATION - EXECUTION SCRIPT
# Generated: 2025-10-18
# ============================================================================

set -e  # Exit on error

echo "======================================================================"
echo "COMPANY ENTITY OPTIMIZATION"
echo "======================================================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Step 1: Backup
echo -e "${YELLOW}[STEP 1/6] Creating database backup...${NC}"
docker-compose exec -T database pg_dump -U luminai_user luminai_db > backup_company_before_optimization_$(date +%Y%m%d_%H%M%S).sql
echo -e "${GREEN}✓ Backup created${NC}"
echo ""

# Step 2: Show current state
echo -e "${YELLOW}[STEP 2/6] Current Company properties count...${NC}"
CURRENT_COUNT=$(docker-compose exec -T database psql -U luminai_user -d luminai_db -t -c "SELECT COUNT(*) FROM generator_property WHERE entity_id = '0199cadd-62b3-768e-b8ab-7d84650ebd47';")
echo -e "Current properties: ${BLUE}$CURRENT_COUNT${NC}"
echo ""

# Step 3: Execute optimization
echo -e "${YELLOW}[STEP 3/6] Executing optimization SQL script...${NC}"
echo -e "${RED}WARNING: This will modify the database!${NC}"
read -p "Continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Aborted."
    exit 1
fi

docker-compose exec -T database psql -U luminai_user -d luminai_db < company_optimization.sql
echo -e "${GREEN}✓ SQL script executed${NC}"
echo ""

# Step 4: Verify changes
echo -e "${YELLOW}[STEP 4/6] Verifying changes...${NC}"
NEW_COUNT=$(docker-compose exec -T database psql -U luminai_user -d luminai_db -t -c "SELECT COUNT(*) FROM generator_property WHERE entity_id = '0199cadd-62b3-768e-b8ab-7d84650ebd47';")
echo -e "New properties count: ${BLUE}$NEW_COUNT${NC}"

if [ "$NEW_COUNT" -eq 51 ]; then
    echo -e "${GREEN}✓ Property count is correct (51)${NC}"
else
    echo -e "${RED}✗ Property count is incorrect. Expected 51, got $NEW_COUNT${NC}"
    exit 1
fi
echo ""

# Step 5: Show field renames
echo -e "${YELLOW}[STEP 5/6] Checking field renames...${NC}"
RENAMED_FIELDS=$(docker-compose exec -T database psql -U luminai_user -d luminai_db -c "SELECT property_name FROM generator_property WHERE entity_id = '0199cadd-62b3-768e-b8ab-7d84650ebd47' AND property_name IN ('taxId', 'billingAddress', 'coordinates', 'mobilePhone', 'phone', 'primaryContactName');")
echo "$RENAMED_FIELDS"
echo ""

# Step 6: Next steps
echo -e "${GREEN}======================================================================"
echo "OPTIMIZATION COMPLETE!"
echo "======================================================================${NC}"
echo ""
echo -e "${YELLOW}NEXT STEPS:${NC}"
echo "1. Review the changes in the database"
echo "2. Regenerate the Company entity PHP class:"
echo "   ${BLUE}php bin/console app:generate:entity Company${NC}"
echo ""
echo "3. Create Doctrine migration:"
echo "   ${BLUE}php bin/console make:migration${NC}"
echo ""
echo "4. Review the migration file carefully!"
echo ""
echo "5. Run the migration:"
echo "   ${BLUE}php bin/console doctrine:migrations:migrate${NC}"
echo ""
echo "6. Update code references to renamed fields:"
echo "   - document → taxId"
echo "   - address → billingAddress"
echo "   - geo → coordinates"
echo "   - celPhone → mobilePhone"
echo "   - businesPhone → phone"
echo "   - contactName → primaryContactName"
echo ""
echo "7. Update forms, templates, and API configurations"
echo ""
echo "8. Run tests:"
echo "   ${BLUE}php bin/phpunit${NC}"
echo ""
echo -e "${GREEN}Documentation:${NC}"
echo "- Summary: COMPANY_OPTIMIZATION_SUMMARY.md"
echo "- Quick Ref: COMPANY_QUICK_REFERENCE.md"
echo "- Field Map: COMPANY_FIELD_MAPPING.md"
echo "- JSON Report: company_optimization_report.json"
echo ""
echo -e "${YELLOW}Backup location:${NC}"
ls -lh backup_company_before_optimization_*.sql | tail -1
echo ""

