import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// Convert number (0-63) to 6-line hexagram array (top to bottom)
function numberToHexagram(num) {
  const lines = [];
  for (let i = 5; i >= 0; i--) {
    const bit = (num >> i) & 1;
    lines.push(bit === 1 ? 'solid' : 'broken');
  }
  return lines;
}

// Generate all 64 hexagrams as object { 1: [...], ..., 64: [...] }
export function generateHexagrams() {
  const hexagrams = {};
  for (let i = 0; i < 64; i++) {
    hexagrams[i + 1] = numberToHexagram(i);
  }
  return hexagrams;
}

// Create an SVG string for a single hexagram (6 lines)
function createHexagramSVG(lines) {
  // Constants for drawing lines
  const width = 120;
  const height = 180;
  const lineHeight = 20;
  const lineLength = 100;
  const startX = 10;
  const centerX = width / 2;
  const lineSpacing = 25;

  let svgLines = '';

  // Draw 6 lines from top (line 1) to bottom (line 6)
  for (let i = 0; i < 6; i++) {
    const y = lineSpacing * i + 20;
    if (lines[i] === 'solid') {
      // Solid line: full horizontal
      svgLines += `<line x1="${startX}" y1="${y}" x2="${startX + lineLength}" y2="${y}" stroke="black" stroke-width="5" />\n`;
    } else {
      // Broken line: two solid segments with gap in the middle
      const segmentLength = (lineLength - 20) / 2;
      svgLines += `<line x1="${startX}" y1="${y}" x2="${startX + segmentLength}" y2="${y}" stroke="black" stroke-width="5" />\n`;
      svgLines += `<line x1="${startX + segmentLength + 20}" y1="${y}" x2="${startX + lineLength}" y2="${y}" stroke="black" stroke-width="5" />\n`;
    }
  }

  return `<?xml version="1.0" encoding="UTF-8"?>
<svg width="${width}" height="${height}" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="I Ching hexagram">
  <rect width="100%" height="100%" fill="white" />
  ${svgLines}
</svg>`;
}

// Write all hexagrams as SVG files to ./hexagrams folder
async function writeHexagramSVGs() {
  const hexagrams = generateHexagrams();
  const __filename = fileURLToPath(import.meta.url);
  const __dirname = path.dirname(__filename);
  const outputDir = path.join(__dirname, 'hexagrams');

  // Create output folder if missing
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir);
  }

  for (const [num, lines] of Object.entries(hexagrams)) {
    const svgContent = createHexagramSVG(lines);
    const filePath = path.join(outputDir, `hexagram-${num}.svg`);
    await fs.promises.writeFile(filePath, svgContent, 'utf8');
    console.log(`Wrote ${filePath}`);
  }
}

// Run script if called directly
if (process.argv[1] === fileURLToPath(import.meta.url)) {
  writeHexagramSVGs().then(() => {
    console.log('All hexagrams generated.');
  }).catch(err => {
    console.error('Error generating hexagrams:', err);
  });
}
