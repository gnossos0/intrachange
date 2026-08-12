// Reference Portal navigation behavior
// Swaps panels with minimal state handling

function loadHexIntoContent(hexNum, label = "") {
    const container = document.getElementById('reference-content');
    if (!container) return;

    const hexUrl = `/intrachange/ref/tolteciching/hex${hexNum.toString().padStart(2, '0')}.html`;
    
    console.log(`Fetching hexagram content from: ${hexUrl}`);
    fetch(hexUrl)
        .then(response => {
            console.log(`Hexagram fetch response status: ${response.status}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(html => {
            // Fix relative image paths in the fetched HTML
            // Change images/XX.jpg to tolteciching/images/XX.jpg (relative from /ref/)
            html = html.replace(/src="images\//g, 'src="tolteciching/images/');
            html = html.replace(/href="images\//g, 'href="tolteciching/images/');
            
            // Fix SVG paths: ../../img/hex/ to ../img/hex/ (relative from /ref/)
            html = html.replace(/src="\.\.\/\.\.\/img\//g, 'src="../img/');
            html = html.replace(/href="\.\.\/\.\.\/img\//g, 'href="../img/');
            
            const block = document.createElement("div");
            block.classList.add("hex-block");

            block.innerHTML = `
                <h3>${label} Hexagram ${hexNum}</h3>
                ${html}
            `;

            container.appendChild(block);
        })
        .catch(error => {
            console.warn(`Failed to load hexagram ${hexNum}:`, error);
            const block = document.createElement("div");
            block.classList.add("hex-block");
            block.innerHTML = `
                <h3>${label} Hexagram ${hexNum}</h3>
                <p style="color: #999; font-style: italic;">Content not available</p>
            `;
            container.appendChild(block);
        });
}

document.addEventListener("DOMContentLoaded", () => {
    console.log('🚀 Initializing Reference Portal...');
    loadBlogData();
    handleBackNavigation();
});

const navItems = document.querySelectorAll('.nav-item');
const panels = document.querySelectorAll('.panel');

const setActivePanel = (targetId) => {
  panels.forEach((panel) => {
    const isVisible = panel.id === targetId;
    panel.classList.toggle('is-visible', isVisible);
    panel.hidden = !isVisible;
  });

  navItems.forEach((item) => {
    const isActive = item.dataset.target === targetId;
    item.classList.toggle('is-active', isActive);
    item.setAttribute('aria-selected', String(isActive));
  });
};

navItems.forEach((item) => {
  item.addEventListener('click', () => {
    setActivePanel(item.dataset.target);
  });
});

// Default to home on load
setActivePanel('home');

// Blog Data Management
let blogData = [];

// Safe HTML escaping function
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

// Load blog data from JSON
async function loadBlogData() {
    try {
        console.log('📚 Loading blog data...');
        const response = await fetch('./data/tolteciching_blog.json');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        blogData = await response.json();
        console.log(`✅ Loaded ${blogData.length} blog posts`);
        
        // Populate dropdown categories
        populateDropdown();
        
    } catch (error) {
        console.error('❌ Failed to load blog data:', error);
        showDropdownError();
    }
}

// Populate dropdown with categories from blog data  
function populateDropdown() {
    const dropdownMenu = document.getElementById('categories-dropdown-menu');
    if (!dropdownMenu) {
        console.error('❌ Dropdown menu not found');
        return;
    }
    
    // Extract unique categories
    const allCategories = new Set();
    blogData.forEach(post => {
        if (post.categories && Array.isArray(post.categories)) {
            post.categories.forEach(category => {
                allCategories.add(category.trim());
            });
        }
    });
    
    // Sort categories alphabetically
    const sortedCategories = Array.from(allCategories).sort();
    console.log(`📂 Found ${sortedCategories.length} unique categories`);
    
    // Clear existing content
    dropdownMenu.innerHTML = '';
    
    // Add "All Posts" option
    const allPostsButton = document.createElement('button');
    allPostsButton.className = 'dropdown-option';
    allPostsButton.type = 'button';
    allPostsButton.role = 'option';
    allPostsButton.textContent = 'Show All Posts';
    allPostsButton.dataset.category = 'ALL';
    dropdownMenu.appendChild(allPostsButton);
    
    // Add divider
    const divider = document.createElement('div');
    divider.className = 'dropdown-heading';
    divider.textContent = `Categories (${sortedCategories.length})`;
    dropdownMenu.appendChild(divider);
    
    // Add category buttons
    sortedCategories.forEach(category => {
        const button = document.createElement('button');
        button.className = 'dropdown-option';
        button.type = 'button';
        button.role = 'option';
        button.textContent = category;
        button.dataset.category = category;
        dropdownMenu.appendChild(button);
    });
    
    console.log('✅ Categories populated in dropdown');
    
    // Add click handlers to all dropdown options
    addDropdownHandlers();
}

// Add click handlers to dropdown options
function addDropdownHandlers() {
    const dropdownMenu = document.getElementById('categories-dropdown-menu');
    
    if (!dropdownMenu) {
        console.error('❌ Dropdown menu not found!');
        return;
    }
    
    console.log('✅ Adding dropdown handlers');
    
    dropdownMenu.addEventListener('click', (event) => {
        console.log('📱 Dropdown clicked:', event.target);
        
        if (event.target.classList.contains('dropdown-option')) {
            const category = event.target.dataset.category;
            const categoryName = event.target.textContent;
            
            console.log(`🔍 Selected category: ${categoryName} (${category})`);
            
            // Close dropdown
            const dropdown = document.getElementById('ref-categories');
            if (dropdown && dropdown.open) {
                dropdown.open = false;
            }
            
            // Filter and display results
            if (category === 'ALL') {
                setActivePanel('home');
                displayBlogResults(blogData, 'All Posts');
            } else {
                setActivePanel('home');
                filterByCategory(category);
            }
        }
    });
}

// Filter posts by category
function filterByCategory(selectedCategory) {
    const filteredPosts = blogData.filter(post => 
        post.categories && post.categories.includes(selectedCategory)
    );
    
    displayBlogResults(filteredPosts, selectedCategory);
}

// Display blog results safely in the main content area
function displayBlogResults(posts, categoryTitle) {
    console.log(`🎯 displayBlogResults called with ${posts.length} posts for: ${categoryTitle}`);
    
    const content = document.getElementById('reference-content');
    if (!content) {
        console.error('❌ Content area not found');
        return;
    }
    
    // Clear content
    content.innerHTML = '';
    
    // Create title
    const title = document.createElement('h2');
    title.textContent = categoryTitle;
    title.style.cssText = 'margin: 0 0 20px 0; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; font-family: Arial, Helvetica, sans-serif;';
    content.appendChild(title);
    
    if (posts.length === 0) {
        const noResults = document.createElement('p');
        noResults.textContent = `No posts found in "${categoryTitle}"`;
        noResults.style.cssText = 'color: #666; font-style: italic; font-family: Arial, Helvetica, sans-serif;';
        content.appendChild(noResults);
        return;
    }
    
    // Create container for posts
    const postsContainer = document.createElement('div');
    
    posts.forEach(post => {
        // Create post article element
        const article = document.createElement('article');
        article.style.cssText = 'margin-bottom: 25px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 6px; background: #f8f9fa; font-family: Arial, Helvetica, sans-serif;';
        
        // Post title
        const postTitle = document.createElement('h3');
        postTitle.textContent = post.title || 'Untitled';
        postTitle.style.cssText = 'margin: 0 0 10px 0; color: #2c3e50; font-size: 18px; font-family: Arial, Helvetica, sans-serif;';
        article.appendChild(postTitle);
        
        // Post excerpt
        if (post.excerpt) {
            const excerpt = document.createElement('p');
            excerpt.textContent = post.excerpt;
            excerpt.style.cssText = 'margin: 10px 0; color: #555; font-size: 14px; line-height: 1.6; font-family: Arial, Helvetica, sans-serif;';
            article.appendChild(excerpt);
        }
        
        // Post metadata
        const metadata = document.createElement('div');
        metadata.style.cssText = 'margin-top: 10px; font-size: 12px; color: #999; font-family: Arial, Helvetica, sans-serif;';
        
        if (post.date) {
            metadata.innerHTML += `<span>📅 ${escapeHtml(post.date)}</span> • `;
        }
        
        if (post.author) {
            metadata.innerHTML += `<span>✍️ ${escapeHtml(post.author)}</span> • `;
        }
        
        article.appendChild(metadata);
        
        // Read more link if available
        if (post.url || post.link) {
            const link = document.createElement('a');
            link.href = post.url || post.link;
            link.style.cssText = 'display: inline-block; margin-top: 12px; color: #3498db; text-decoration: none; font-weight: bold; cursor: pointer; font-family: Arial, Helvetica, sans-serif;';
            link.textContent = 'Read Full Post →';
            
            link.addEventListener('click', (e) => {
                if (post.url) {
                    e.preventDefault();
                    
                    // Store state for back navigation
                    sessionStorage.setItem('ref-portal-returning', 'true');
                    sessionStorage.setItem('ref-portal-category', categoryTitle);
                    sessionStorage.setItem('ref-portal-results', JSON.stringify(posts));
                    
                    // Try popup first
                    try {
                        const popupWindow = window.open(
                            post.url,
                            'blog-article',
                            'width=900,height=700,scrollbars=yes,resizable=yes'
                        );
                        
                        if (!popupWindow) {
                            // Popup blocked, open in same window
                            window.location.href = post.url;
                        }
                    } catch (error) {
                        window.location.href = post.url;
                    }
                }
            });
            
            article.appendChild(link);
        }
        
        postsContainer.appendChild(article);
    });
    
    content.appendChild(postsContainer);
    console.log(`✅ Displayed ${posts.length} blog posts`);
}

// Back navigation functionality
function handleBackNavigation() {
    const isReturning = sessionStorage.getItem('ref-portal-returning');
    const backButton = document.getElementById('back-button');
    
    if (!backButton || !isReturning) return;
    
    backButton.style.display = 'inline-block';
    
    backButton.addEventListener('click', () => {
        const storedCategory = sessionStorage.getItem('ref-portal-category');
        const storedResults = sessionStorage.getItem('ref-portal-results');
        
        if (storedCategory && storedResults) {
            try {
                const results = JSON.parse(storedResults);
                
                // Restore blog results
                displayBlogResults(results, storedCategory);
                console.log(`🔙 Restored ${results.length} posts for category: ${storedCategory}`);
            } catch (error) {
                console.error('❌ Error restoring results:', error);
            }
        }
        
        // Clear session storage
        sessionStorage.removeItem('ref-portal-returning');
        sessionStorage.removeItem('ref-portal-category');
        sessionStorage.removeItem('ref-portal-results');
        
        backButton.style.display = 'none';
    });
}

function showDropdownError() {
    const dropdownMenu = document.getElementById('categories-dropdown-menu');
    if (dropdownMenu) {
        dropdownMenu.innerHTML = '<div class="dropdown-heading" style="color: #e74c3c;">Error loading categories</div>';
    }
}

// Initialize blog functionality when page loads
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Initializing Reference Portal...');
    loadBlogData();
    handleBackNavigation();
});
