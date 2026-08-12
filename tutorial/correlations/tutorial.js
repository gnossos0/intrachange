// Tutorial JavaScript - Intrachange
let currentPage = 1;
const totalPages = 6;

// Page content mapping
const pageFiles = {
    1: 'pages/01_explore_now.html',
    2: 'pages/02_modification_of_chess_rules.html',
    3: 'pages/03_symbolism_of_the_move.html',
    4: 'pages/04_concentration.html',
    5: 'pages/05_nature_of_the_game.html',
    6: 'pages/06_archives_and_orders.html'
};

// Initialize tutorial
document.addEventListener('DOMContentLoaded', function() {
    // Load saved progress from localStorage
    const savedPage = localStorage.getItem('intrachange_tutorial_page');
    if (savedPage && savedPage >= 1 && savedPage <= totalPages) {
        currentPage = parseInt(savedPage);
    }
    
    // Load initial page
    loadPage(currentPage);
    updateUI();
});

// Load page content
async function loadPage(pageNumber) {
    try {
        const response = await fetch(pageFiles[pageNumber]);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const content = await response.text();
        document.getElementById('tutorialContent').innerHTML = content;
        
        // Scroll to top of content
        document.getElementById('tutorialContent').scrollTop = 0;
        
        // Save progress
        localStorage.setItem('intrachange_tutorial_page', pageNumber.toString());
        
    } catch (error) {
        console.error('Error loading page:', error);
        document.getElementById('tutorialContent').innerHTML = `
            <div class="page-content">
                <h2>Page ${pageNumber}</h2>
                <p>Error loading content. Please try again.</p>
            </div>
        `;
    }
}

// Update UI elements
function updateUI() {
    // Update progress bar
    const progressPercent = (currentPage / totalPages) * 100;
    document.getElementById('progressFill').style.width = progressPercent + '%';
    document.getElementById('progressText').textContent = Math.round(progressPercent) + '% Complete';
    
    // Update page indicator
    document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
    
    // Update navigation buttons
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    prevBtn.disabled = (currentPage === 1);
    nextBtn.disabled = (currentPage === totalPages);
    
    if (currentPage === totalPages) {
        nextBtn.textContent = 'Complete Tutorial';
        nextBtn.classList.add('complete');
    } else {
        nextBtn.textContent = 'Next →';
        nextBtn.classList.remove('complete');
    }
    
    // Update table of contents
    updateTOC();
}

// Update table of contents
function updateTOC() {
    const tocLinks = document.querySelectorAll('.toc-link');
    tocLinks.forEach((link, index) => {
        const pageNum = index + 1;
        link.classList.remove('active', 'completed');
        
        if (pageNum === currentPage) {
            link.classList.add('active');
        } else if (pageNum < currentPage) {
            link.classList.add('completed');
        }
    });
}

// Navigation functions
function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        loadPage(currentPage);
        updateUI();
    } else if (currentPage === totalPages) {
        completeTutorial();
    }
}

function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        loadPage(currentPage);
        updateUI();
    }
}

function goToPage(pageNumber) {
    if (pageNumber >= 1 && pageNumber <= totalPages) {
        currentPage = pageNumber;
        loadPage(currentPage);
        updateUI();
    }
}

// Tutorial completion
function completeTutorial() {
    localStorage.setItem('intrachange_tutorial_completed', 'true');
    localStorage.setItem('intrachange_tutorial_completion_date', new Date().toISOString());
    
    // Show completion message
    document.getElementById('tutorialContent').innerHTML = `
        <div class="page-content" style="text-align: center;">
            <h2>🎉 Congratulations!</h2>
            <p style="font-size: 1.2rem; margin: 20px 0;">You have completed the Intrachange Tutorial!</p>
            <p>You now understand the deep principles behind this transformative game.</p>
            <div style="margin: 30px 0;">
                <button onclick="window.location.href='../dashboard.php'" class="nav-btn primary" style="margin: 10px; padding: 15px 30px; font-size: 1.1rem;">
                    🏆 Go to Dashboard
                </button>
                <button onclick="window.location.href='../index.html'" class="nav-btn" style="margin: 10px; padding: 15px 30px; font-size: 1.1rem;">
                    🎮 Start Playing
                </button>
            </div>
            <p style="font-style: italic; color: #d4af37; margin-top: 20px;">
                "The master has learned not where to put the pieces, but where to put himself." - Ancient Intrachange Proverb
            </p>
        </div>
    `;
    
    updateUI();
}

// Utility functions
function restartTutorial() {
    if (confirm('Are you sure you want to restart the tutorial from the beginning?')) {
        currentPage = 1;
        localStorage.setItem('intrachange_tutorial_page', '1');
        localStorage.removeItem('intrachange_tutorial_completed');
        loadPage(currentPage);
        updateUI();
    }
}

function skipToEnd() {
    if (confirm('Are you sure you want to skip to the end of the tutorial?')) {
        currentPage = totalPages;
        loadPage(currentPage);
        updateUI();
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(event) {
    if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
        event.preventDefault();
        previousPage();
    } else if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
        event.preventDefault();
        nextPage();
    } else if (event.key === 'Home') {
        event.preventDefault();
        goToPage(1);
    } else if (event.key === 'End') {
        event.preventDefault();
        goToPage(totalPages);
    }
});

// Auto-save progress periodically
setInterval(() => {
    localStorage.setItem('intrachange_tutorial_page', currentPage.toString());
}, 30000); // Save every 30 seconds
