// Correlation Module Template - Main Logic

import { qs, qsa, setPageTitle, formatHexagramNumber } from '../shared/correlations-utils.js';
import { attachClickHandler } from '../shared/correlations-events.js';
import { selectHexagram, clearAllHighlights } from '../shared/correlation-logic.js';

// Module state
let moduleData = null;
let currentMoveIndex = 0;

// Initialize module
async function initModule() {
  try {
    console.log('Loading correlation module...');
    
    // Add small delay to ensure DOM is fully rendered
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Load module content
    const response = await fetch('./content.json');
    
    if (!response.ok) {
      throw new Error(`Failed to load content: ${response.status} ${response.statusText}`);
    }
    
    moduleData = await response.json();
    console.log('Module data loaded:', moduleData.title);
    
    // Validate module data
    if (!moduleData.moves || !Array.isArray(moduleData.moves) || moduleData.moves.length === 0) {
      throw new Error('Invalid module data: no moves found');
    }
    
    // Set page title
    setPageTitle(moduleData.title);
    
    // Render initial content with delay to ensure DOM is ready
    setTimeout(() => {
      // renderHeader(); // Temporarily disabled for debugging
      renderMove(0);
      setupNavigation();
      console.log('Module initialization complete');
    }, 50);
    
  } catch (error) {
    console.error('Failed to load module data:', error);
    showError(`Failed to load module content: ${error.message}`);
  }
}

// Render header content
function renderHeader() {
  try {
    if (!moduleData) {
      console.log('No module data available');
      return;
    }
    
    console.log('renderHeader called, looking for #correlation-header');
    
    // Extract correlation pattern from title (e.g., "4::4" from "Correlation 4::4 Template")
    const correlationHeader = document.querySelector('#correlation-header');
    console.log('correlationHeader element:', correlationHeader);
    
    if (!correlationHeader) {
      console.warn('Correlation header element not found');
      return;
    }
    
    const correlationMatch = moduleData.title ? moduleData.title.match(/(\d+)::(\d+)/) : null;
    if (correlationMatch) {
      correlationHeader.textContent = `Correlation ${correlationMatch[0]}`;
    } else {
      correlationHeader.textContent = 'Correlation #::#';
    }
    
    console.log('Header rendered successfully');
  } catch (error) {
    console.error('Error in renderHeader:', error);
  }
}

// Render specific move
function renderMove(moveIndex) {
  if (!moduleData || !moduleData.moves[moveIndex]) {
    console.warn(`Invalid move index: ${moveIndex}`);
    return;
  }
  
  const move = moduleData.moves[moveIndex];
  currentMoveIndex = moveIndex;
  
  console.log(`Rendering move ${moveIndex + 1}: ${move.coordinates.from} to ${move.coordinates.to}`);
  
  // Clear previous highlights
  clearAllHighlights();
  
  // Update move counter with animation
  const counter = qs('#move-counter');
  counter.style.opacity = '0.5';
  setTimeout(() => {
    counter.textContent = `${moveIndex + 1} of ${moduleData.moves.length}`;
    counter.style.opacity = '1';
  }, 100);
  
  // Render move content
  renderMoveImage(move);
  renderMoveDetails(move);
  renderKeywords(move);
  renderInterpretation(move);
  
  // Update navigation state
  updateNavigation();
}

// Render move image
function renderMoveImage(move) {
  const imageContainer = qs('#move-image');
  
  if (!move.image) {
    imageContainer.innerHTML = '<div class="image-placeholder">No image available</div>';
    return;
  }
  
  const imagePath = `../shared/images/patterns/${move.image}`;
  
  // Show loading state
  imageContainer.innerHTML = '<div class="image-placeholder">Loading image...</div>';
  
  // Create image with proper error handling
  const img = new Image();
  img.onload = () => {
    imageContainer.innerHTML = `<img src="${imagePath}" alt="Move ${move.id}: ${move.coordinates.from} to ${move.coordinates.to}" class="move-image" />`;
  };
  img.onerror = () => {
    imageContainer.innerHTML = `<div class="image-placeholder">Image not found: ${move.image}</div>`;
  };
  img.src = imagePath;
}

// Render move details
function renderMoveDetails(move) {
  const detailsContainer = qs('#move-details');
  
  // Validate move data
  if (!move.coordinates || !move.hexagrams) {
    detailsContainer.innerHTML = '<div class="corr-card"><div class="corr-card-content">Invalid move data</div></div>';
    return;
  }
  
  const fromHex = move.hexagrams.from ? formatHexagramNumber(move.hexagrams.from) : 'Unknown';
  const toHex = move.hexagrams.to ? formatHexagramNumber(move.hexagrams.to) : 'Unknown';
  
  detailsContainer.innerHTML = `
    <div class="corr-card">
      <div class="corr-card-title">Movement</div>
      <div class="corr-card-content">
        <p><strong>From:</strong> ${move.coordinates.from || 'Unknown'} (Hexagram ${fromHex})</p>
        <p><strong>To:</strong> ${move.coordinates.to || 'Unknown'} (Hexagram ${toHex})</p>
      </div>
    </div>
  `;
}

// Render keywords
function renderKeywords(move) {
  const keywordsContainer = qs('#keywords-list');
  
  // Validate keywords data
  if (!move.keywords || !move.hexagrams) {
    keywordsContainer.innerHTML = '<div class="corr-card"><div class="corr-card-content">No keywords available</div></div>';
    return;
  }
  
  const fromKeyword = move.keywords.from || 'Unknown';
  const toKeyword = move.keywords.to || 'Unknown';
  const fromHex = formatHexagramNumber(move.hexagrams.from);
  const toHex = formatHexagramNumber(move.hexagrams.to);
  
  keywordsContainer.innerHTML = `
    <div class="keywords-grid">
      <div class="corr-card keyword-card">
        <div class="corr-card-title">From: ${fromKeyword}</div>
        <div class="corr-card-content">Hexagram ${fromHex}</div>
      </div>
      <div class="corr-card keyword-card">
        <div class="corr-card-title">To: ${toKeyword}</div>
        <div class="corr-card-content">Hexagram ${toHex}</div>
      </div>
    </div>
  `;
  
  // Add click handlers for keyword cards with error handling
  try {
    qsa('.keyword-card').forEach((card, index) => {
      const hexNum = index === 0 ? move.hexagrams.from : move.hexagrams.to;
      if (hexNum && typeof hexNum === 'number') {
        attachClickHandler(card, () => selectHexagram(hexNum, card));
      }
    });
  } catch (error) {
    console.warn('Failed to attach keyword click handlers:', error);
  }
}

// Render interpretation
function renderInterpretation(move) {
  const interpretationContainer = qs('#interpretation-text');
  
  if (!move.interpretation || typeof move.interpretation !== 'string') {
    interpretationContainer.textContent = 'No interpretation available for this move.';
  } else {
    interpretationContainer.textContent = move.interpretation;
  }
}

// Setup navigation handlers
function setupNavigation() {
  const prevBtn = qs('#prev-btn');
  const nextBtn = qs('#next-btn');
  
  // Click handlers
  attachClickHandler(prevBtn, () => {
    if (currentMoveIndex > 0) {
      renderMove(currentMoveIndex - 1);
    }
  });
  
  attachClickHandler(nextBtn, () => {
    if (currentMoveIndex < moduleData.moves.length - 1) {
      renderMove(currentMoveIndex + 1);
    }
  });
  
  // Keyboard navigation
  document.addEventListener('keydown', handleKeyboardNavigation);
}

// Handle keyboard navigation
function handleKeyboardNavigation(event) {
  if (!moduleData || !moduleData.moves) return;
  
  switch(event.key) {
    case 'ArrowLeft':
    case 'ArrowUp':
      event.preventDefault();
      if (currentMoveIndex > 0) {
        renderMove(currentMoveIndex - 1);
      }
      break;
    case 'ArrowRight':
    case 'ArrowDown':
      event.preventDefault();
      if (currentMoveIndex < moduleData.moves.length - 1) {
        renderMove(currentMoveIndex + 1);
      }
      break;
    case 'Home':
      event.preventDefault();
      renderMove(0);
      break;
    case 'End':
      event.preventDefault();
      renderMove(moduleData.moves.length - 1);
      break;
  }
}

// Update navigation button states
function updateNavigation() {
  const prevBtn = qs('#prev-btn');
  const nextBtn = qs('#next-btn');
  
  prevBtn.disabled = currentMoveIndex === 0;
  nextBtn.disabled = currentMoveIndex === moduleData.moves.length - 1;
}

// Show error message
function showError(message) {
  console.error('Module error:', message);
  
  // Try to update header if it exists
  const headerEl = qs('#correlation-header');
  if (headerEl) {
    headerEl.textContent = 'Error Loading Module';
  }
  
  // Hide navigation if it exists
  const navEl = qs('.module-navigation');
  if (navEl) navEl.style.display = 'none';
  
  // Show error in content area if it exists
  const contentEl = qs('.module-content');
  if (contentEl) {
    contentEl.innerHTML = `
      <div style="text-align: center; padding: 2rem; color: #ccc;">
        <h3 style="color: #ff6b6b;">Unable to load module</h3>
        <p>${message}</p>
        <p>Please check your connection and try again.</p>
      </div>
    `;
  } else {
    // Fallback: create error display in body if content area doesn't exist
    document.body.innerHTML = `
      <div style="text-align: center; padding: 2rem; color: #ccc; background: #1a1a1a; min-height: 100vh;">
        <h3 style="color: #ff6b6b;">Unable to load module</h3>
        <p>${message}</p>
        <p>Please check your connection and try again.</p>
      </div>
    `;
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('DOM loaded, initializing correlation module...');
  initModule();
});