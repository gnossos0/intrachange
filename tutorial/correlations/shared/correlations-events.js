// Correlation event handling utilities

import { applyHoverHighlight, clearHoverHighlight, applySelectionHighlight } from './correlations-utils.js';

// Attach hover handlers
function attachHoverHandlers(element, onHoverIn, onHoverOut) {
  if (!element) return;
  
  element.addEventListener('mouseenter', onHoverIn);
  element.addEventListener('mouseleave', onHoverOut);
}

// Attach click handler
function attachClickHandler(element, handler) {
  if (!element || !handler) return;
  
  element.addEventListener('click', handler);
}

// Add highlight class
function addHighlight(element) {
  if (element) {
    element.classList.add('corr-highlight');
  }
}

// Remove highlight class
function removeHighlight(element) {
  if (element) {
    element.classList.remove('corr-highlight');
  }
}

// Toggle selection class
function toggleSelect(element) {
  if (element) {
    element.classList.toggle('corr-selected');
  }
}

export { attachHoverHandlers, attachClickHandler, addHighlight, removeHighlight, toggleSelect };

export default {
  attachHoverHandlers,
  attachClickHandler,
  addHighlight,
  removeHighlight,
  toggleSelect
};