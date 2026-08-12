// Correlation utility functions (approved minimal version)

// Query helpers
export const qs = (sel, el = document) => el.querySelector(sel);
export const qsa = (sel, el = document) => [...el.querySelectorAll(sel)];

// Set dynamic page title
export function setPageTitle(title) {
  if (typeof title === "string") {
    document.title = `Intrachange — ${title}`;
  }
}

// Format hexagram numbers (always 1–64 as two digits)
export function formatHexagramNumber(num) {
  if (typeof num !== "number") return "";
  return num.toString().padStart(2, "0");
}

// Basic image preloader (non-blocking)
export function preloadImage(src) {
  if (!src) return;
  const img = new Image();
  img.src = src;
}

// Apply hover highlight
function applyHoverHighlight(element) {
  if (element) {
    element.classList.add('corr-hover');
  }
}

// Clear hover highlight
function clearHoverHighlight(element) {
  if (element) {
    element.classList.remove('corr-hover');
  }
}

// Apply or clear selection highlight
function applySelectionHighlight(element, isSelected) {
  if (element) {
    if (isSelected) {
      element.classList.add('corr-selected');
    } else {
      element.classList.remove('corr-selected');
    }
  }
}

// Dim non-selected elements
function dimNonSelected(elements, selectedIndex) {
  if (elements && Array.isArray(elements)) {
    elements.forEach((element, index) => {
      if (element && index !== selectedIndex) {
        element.classList.add('corr-dimmer');
      }
    });
  }
}

// Clear dim from all elements
function clearDim(elements) {
  if (elements && Array.isArray(elements)) {
    elements.forEach(element => {
      if (element) {
        element.classList.remove('corr-dimmer');
      }
    });
  }
}

export { applyHoverHighlight, clearHoverHighlight, applySelectionHighlight, dimNonSelected, clearDim };