<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\OrganizationTrait;
use App\Entity\Trait\SoftDeletableTrait;
use App\Repository\ProductCategoryRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * ProductCategory Entity - Hierarchical Product Categorization System
 *
 * Implements enterprise-grade product taxonomy with:
 * - Hierarchical parent-child relationships (unlimited depth)
 * - Multi-tenant organization isolation
 * - Soft delete functionality
 * - SEO optimization (slug, metadata)
 * - Display control (active, visible, sortOrder)
 * - API Platform integration
 * - Comprehensive metadata tracking
 * - Image and icon support
 * - Full-text search optimization
 *
 * Best Practices (2025):
 * - Maximum 2-3 sub-category levels for UX
 * - User-friendly terminology
 * - Three-click rule compliance
 * - SEO-friendly slugs
 * - Consistent categorization
 * - Regular taxonomy updates
 *
 * @see https://www.shopify.com/blog/product-taxonomy
 * @see https://pimcore.com/en/resources/blog/product-taxonomy-best-practices-a-pim-perspective_a360017
 */
#[ORM\Entity(repositoryClass: ProductCategoryRepository::class)]
#[ORM\Table(name: 'product_category')]
#[ORM\Index(name: 'idx_category_name', columns: ['category_name'])]
#[ORM\Index(name: 'idx_category_slug', columns: ['category_slug'])]
#[ORM\Index(name: 'idx_category_active_visible', columns: ['active', 'visible'])]
#[ORM\Index(name: 'idx_category_parent', columns: ['parent_category_id'])]
#[ORM\Index(name: 'idx_category_sort', columns: ['sort_order'])]
#[ORM\Index(name: 'idx_category_level_path', columns: ['category_level', 'category_path'])]
#[ORM\Index(name: 'idx_category_featured', columns: ['is_featured', 'sort_order'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    security: "is_granted('ROLE_USER')",
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['product_category:read', 'product_category:list']],
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            normalizationContext: ['groups' => ['product_category:read', 'product_category:detail']],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['product_category:write', 'product_category:create']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['product_category:write', 'product_category:update']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['product_category:write', 'product_category:patch']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    normalizationContext: ['groups' => ['product_category:read']],
    denormalizationContext: ['groups' => ['product_category:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 50,
    order: ['categoryPath' => 'ASC', 'sortOrder' => 'ASC']
)]
class ProductCategory extends EntityBase
{
    use OrganizationTrait;
    use SoftDeletableTrait;

    // ====================================
    // CORE IDENTIFICATION (4 fields)
    // ====================================

    /**
     * Category name (e.g., "Electronics", "Computers & Tablets")
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Category name is required')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Category name must be at least 2 characters', maxMessage: 'Category name cannot exceed 255 characters')]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:list'])]
    private string $categoryName;

    /**
     * SEO-friendly URL slug (e.g., "electronics", "computers-tablets")
     * Auto-generated from categoryName if not provided
     */
    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        message: 'Slug must contain only lowercase letters, numbers, and hyphens'
    )]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:list'])]
    private string $categorySlug;

    /**
     * Internal category code/SKU (e.g., "CAT-ELEC-001")
     */
    #[ORM\Column(length: 100, unique: true, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?string $categoryCode = null;

    /**
     * Detailed category description (supports markdown)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 5000)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?string $description = null;

    // ====================================
    // HIERARCHICAL STRUCTURE (4 fields)
    // ====================================

    /**
     * Parent category (null for root categories)
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childCategories')]
    #[ORM\JoinColumn(name: 'parent_category_id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?self $parentCategory = null;

    /**
     * Child categories (subcategories)
     */
    #[ORM\OneToMany(mappedBy: 'parentCategory', targetEntity: self::class, cascade: ['persist'])]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'categoryName' => 'ASC'])]
    #[Groups(['product_category:read', 'product_category:detail'])]
    private Collection $childCategories;

    /**
     * Depth level in hierarchy (0 = root, 1 = first level, etc.)
     * Auto-calculated based on parent
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 10)]
    #[Groups(['product_category:read', 'product_category:list'])]
    private int $categoryLevel = 0;

    /**
     * Full hierarchical path (e.g., "Electronics > Computers > Laptops")
     * Auto-generated from parent hierarchy
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['product_category:read', 'product_category:list'])]
    private ?string $categoryPath = null;

    // ====================================
    // DISPLAY & VISIBILITY (6 fields)
    // ====================================

    /**
     * Category is active (can be used for new products)
     * Convention: "active" NOT "isActive"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:list'])]
    private bool $active = true;

    /**
     * Category is visible to customers (frontend display)
     * Convention: "visible" NOT "isVisible"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:list'])]
    private bool $visible = true;

    /**
     * Display order within same parent (lower = higher priority)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    #[Assert\Range(min: 0, max: 9999)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:list'])]
    private int $sortOrder = 100;

    /**
     * Featured category (promoted display)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:list'])]
    private bool $isFeatured = false;

    /**
     * Display category in navigation menus
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['product_category:read', 'product_category:write'])]
    private bool $showInMenu = true;

    /**
     * Display category on homepage
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['product_category:read', 'product_category:write'])]
    private bool $showOnHomepage = false;

    // ====================================
    // VISUAL ELEMENTS (4 fields)
    // ====================================

    /**
     * Category icon (Bootstrap Icons class, e.g., "bi-laptop")
     */
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Assert\Regex(
        pattern: '/^bi-[a-z0-9-]+$/',
        message: 'Icon must be a valid Bootstrap icon (e.g., bi-laptop)'
    )]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:list'])]
    private ?string $icon = null;

    /**
     * Category image URL/path
     */
    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?string $imageUrl = null;

    /**
     * Thumbnail image URL/path
     */
    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:list'])]
    private ?string $thumbnailUrl = null;

    /**
     * Category color (hex code for visual identification)
     */
    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'Color must be a valid hex color (e.g., #3498db)'
    )]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:list'])]
    private ?string $color = null;

    // ====================================
    // SEO & METADATA (5 fields)
    // ====================================

    /**
     * SEO meta title (overrides categoryName for search engines)
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?string $metaTitle = null;

    /**
     * SEO meta description
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?string $metaDescription = null;

    /**
     * SEO meta keywords (comma-separated)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?string $metaKeywords = null;

    /**
     * Canonical URL for SEO (if category exists on multiple paths)
     */
    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?string $canonicalUrl = null;

    /**
     * Open Graph image for social media sharing
     */
    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?string $ogImageUrl = null;

    // ====================================
    // STATISTICS & ANALYTICS (3 fields)
    // ====================================

    /**
     * Number of products in this category (direct)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['product_category:read', 'product_category:list'])]
    private int $productCount = 0;

    /**
     * Number of products including all subcategories
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['product_category:read', 'product_category:list'])]
    private int $totalProductCount = 0;

    /**
     * Number of views/clicks (for analytics)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['product_category:read', 'product_category:detail'])]
    private int $viewCount = 0;

    // ====================================
    // ADDITIONAL METADATA (3 fields)
    // ====================================

    /**
     * Additional custom attributes (JSON)
     * Can store: commission_rate, tax_category, shipping_class, etc.
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?array $customAttributes = null;

    /**
     * Category tags for filtering/search (e.g., ["seasonal", "promotion", "new"])
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?array $tags = null;

    /**
     * External system IDs for integration (e.g., ERP, PIM, marketplace IDs)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['product_category:read', 'product_category:write', 'product_category:detail'])]
    private ?array $externalIds = null;

    // ====================================
    // CONSTRUCTOR
    // ====================================

    public function __construct()
    {
        parent::__construct();
        $this->childCategories = new ArrayCollection();
    }

    // ====================================
    // LIFECYCLE CALLBACKS
    // ====================================

    /**
     * Pre-persist callback - auto-generate slug and calculate hierarchy
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (empty($this->categorySlug)) {
            $this->categorySlug = $this->generateSlug($this->categoryName);
        }
        $this->updateHierarchyData();
    }

    /**
     * Pre-update callback - recalculate hierarchy if parent changed
     */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updateHierarchyData();
    }

    // ====================================
    // DOMAIN LOGIC METHODS
    // ====================================

    /**
     * Generate URL-friendly slug from text
     */
    private function generateSlug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Update hierarchy-related data (level and path)
     */
    private function updateHierarchyData(): void
    {
        if ($this->parentCategory === null) {
            $this->categoryLevel = 0;
            $this->categoryPath = $this->categoryName;
        } else {
            $this->categoryLevel = $this->parentCategory->getCategoryLevel() + 1;
            $this->categoryPath = $this->parentCategory->getCategoryPath() . ' > ' . $this->categoryName;
        }
    }

    /**
     * Check if category is root (has no parent)
     */
    public function isRoot(): bool
    {
        return $this->parentCategory === null;
    }

    /**
     * Check if category is leaf (has no children)
     */
    public function isLeaf(): bool
    {
        return $this->childCategories->isEmpty();
    }

    /**
     * Get all ancestor categories (breadcrumb trail)
     * @return array<self>
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parentCategory;

        while ($current !== null) {
            array_unshift($ancestors, $current);
            $current = $current->getParentCategory();
        }

        return $ancestors;
    }

    /**
     * Get all descendant categories (recursive)
     * @return array<self>
     */
    public function getDescendants(): array
    {
        $descendants = [];

        foreach ($this->childCategories as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Check if category can be deleted (has no products and no children)
     */
    public function canBeDeleted(): bool
    {
        return $this->productCount === 0 && $this->childCategories->isEmpty();
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): self
    {
        $this->viewCount++;
        return $this;
    }

    /**
     * Update product count
     */
    public function updateProductCount(int $count): self
    {
        $this->productCount = $count;
        return $this;
    }

    /**
     * Update total product count (including subcategories)
     */
    public function updateTotalProductCount(int $count): self
    {
        $this->totalProductCount = $count;
        return $this;
    }

    // ====================================
    // STRING REPRESENTATION
    // ====================================

    public function __toString(): string
    {
        return $this->categoryPath ?? $this->categoryName;
    }

    // ====================================
    // GETTERS AND SETTERS
    // ====================================

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function setCategoryName(string $categoryName): self
    {
        $this->categoryName = $categoryName;
        return $this;
    }

    public function getCategorySlug(): string
    {
        return $this->categorySlug;
    }

    public function setCategorySlug(string $categorySlug): self
    {
        $this->categorySlug = $categorySlug;
        return $this;
    }

    public function getCategoryCode(): ?string
    {
        return $this->categoryCode;
    }

    public function setCategoryCode(?string $categoryCode): self
    {
        $this->categoryCode = $categoryCode;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getParentCategory(): ?self
    {
        return $this->parentCategory;
    }

    public function setParentCategory(?self $parentCategory): self
    {
        $this->parentCategory = $parentCategory;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildCategories(): Collection
    {
        return $this->childCategories;
    }

    public function addChildCategory(self $childCategory): self
    {
        if (!$this->childCategories->contains($childCategory)) {
            $this->childCategories->add($childCategory);
            $childCategory->setParentCategory($this);
        }
        return $this;
    }

    public function removeChildCategory(self $childCategory): self
    {
        if ($this->childCategories->removeElement($childCategory)) {
            if ($childCategory->getParentCategory() === $this) {
                $childCategory->setParentCategory(null);
            }
        }
        return $this;
    }

    public function getCategoryLevel(): int
    {
        return $this->categoryLevel;
    }

    public function setCategoryLevel(int $categoryLevel): self
    {
        $this->categoryLevel = $categoryLevel;
        return $this;
    }

    public function getCategoryPath(): ?string
    {
        return $this->categoryPath;
    }

    public function setCategoryPath(?string $categoryPath): self
    {
        $this->categoryPath = $categoryPath;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): self
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function isShowInMenu(): bool
    {
        return $this->showInMenu;
    }

    public function setShowInMenu(bool $showInMenu): self
    {
        $this->showInMenu = $showInMenu;
        return $this;
    }

    public function isShowOnHomepage(): bool
    {
        return $this->showOnHomepage;
    }

    public function setShowOnHomepage(bool $showOnHomepage): self
    {
        $this->showOnHomepage = $showOnHomepage;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?string $metaKeywords): self
    {
        $this->metaKeywords = $metaKeywords;
        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): self
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }

    public function getOgImageUrl(): ?string
    {
        return $this->ogImageUrl;
    }

    public function setOgImageUrl(?string $ogImageUrl): self
    {
        $this->ogImageUrl = $ogImageUrl;
        return $this;
    }

    public function getProductCount(): int
    {
        return $this->productCount;
    }

    public function setProductCount(int $productCount): self
    {
        $this->productCount = $productCount;
        return $this;
    }

    public function getTotalProductCount(): int
    {
        return $this->totalProductCount;
    }

    public function setTotalProductCount(int $totalProductCount): self
    {
        $this->totalProductCount = $totalProductCount;
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): self
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function getCustomAttributes(): ?array
    {
        return $this->customAttributes;
    }

    public function setCustomAttributes(?array $customAttributes): self
    {
        $this->customAttributes = $customAttributes;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getExternalIds(): ?array
    {
        return $this->externalIds;
    }

    public function setExternalIds(?array $externalIds): self
    {
        $this->externalIds = $externalIds;
        return $this;
    }
}
