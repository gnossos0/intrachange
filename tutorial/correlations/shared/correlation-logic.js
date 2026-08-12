// Correlation logic layer (architecture only - no implementation)

import { applySelectionHighlight, clearHoverHighlight } from './correlations-utils.js';

// Internal element registry (single map only)
const elementRegistry = new Map();

// Select hexagram and apply selection state
function selectHexagram(hexNum, element) {
  clearAllHighlights();
  
  if (element) {
    applySelectionHighlight(element, true);
  }
  
  // TODO: Add correlation computation logic in Task 7
}

// Highlight related elements (placeholder)
function highlightRelated(hexNum) {
  // TODO: Implement relationship highlighting in Task 7
  console.log(`highlightRelated called for hexagram ${hexNum}`);
}

// Clear all highlights from correlation components
function clearAllHighlights() {
  const highlightedElements = document.querySelectorAll('.corr-highlight, .corr-selected');
  
  highlightedElements.forEach(element => {
    element.classList.remove('corr-highlight', 'corr-selected');
  });
}

// Prepare diagram overlay (placeholder)
function prepareDiagramOverlay(hexNum) {
  // TODO: Add diagram rendering logic in Task 7
}

// Register element with hexagram association
function registerElement(element, hexNum) {
  if (element && typeof hexNum === 'number') {
    elementRegistry.set(element, hexNum);
  }
}

export { selectHexagram, highlightRelated, clearAllHighlights, prepareDiagramOverlay, registerElement };

export default {
  selectHexagram,
  highlightRelated,
  clearAllHighlights,
  prepareDiagramOverlay,
  registerElement
};