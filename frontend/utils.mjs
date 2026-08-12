// utils.mjs
import { positionMap } from './positionMap.mjs';
import { hexagrams } from './hexagrams.mjs';

console.log("✅ utils positionMap loaded:", positionMap);

// Convert flat index (0-63) to row and column (0-7)
export function flatToRowCol(index) {
  const row = Math.floor(index / 8);
  const col = index % 8;
  return { row, col };
}

// Convert row and column (0-7) back to flat index (0-63)
export function rowColToFlat(row, col) {
  return row * 8 + col;
}

// ----------------------------
// Coordinate / Hex Helpers
// ----------------------------

// Convert row and col (0-7) to chess coordinate like 'e4'
export function coordsToCoord(row, col) {
  const files = ['a','b','c','d','e','f','g','h'];
  const ranks = ['8','7','6','5','4','3','2','1'];
  return files[col] + ranks[row];
}

// Convert chess coordinate like 'e4' to row and col (0-7)
export function coordToRowCol(coord) {
  if (!coord || coord.length !== 2) return null;
  const file = coord[0].toLowerCase();
  const rank = parseInt(coord[1]);
  if (!'abcdefgh'.includes(file) || isNaN(rank)) return null;
  const col = 'abcdefgh'.indexOf(file);
  const row = 8 - rank;
  return { row, col };
}

// Get row and col from coordinate using positionMap
export function getRowColFromCoord(coord) {
  const entry = positionMap.find(p => p.coord === coord);
  if (!entry) throw new Error(`Coordinate ${coord} not found in positionMap`);
  return { row: entry.row, col: entry.col };
}

// Get hexNum from row/col
export function coordsToHexNum(row, col) {
  const coord = coordsToCoord(row, col);
  return hexagrams[coord]?.hexNum ?? null;
}

// Get hexNum from coordinate
export function coordToHexNum(coord) {
  return hexagrams[coord]?.hexNum ?? null;
}

// Get coordinate from hexNum
export function hexNumToCoord(hexNum) {
  const entry = Object.entries(hexagrams).find(([coord, { hexNum: hn }]) => hn === hexNum);
  return entry ? entry[0] : null;
}

// ----------------------------
// Piece Helpers
// ----------------------------

// Standardize piece code format (e.g., 'WP', 'bK')
export function standardizePieceCode(pieceCode) {
  if (!pieceCode || typeof pieceCode !== 'string') return null;
  const color = pieceCode[0].toLowerCase();
  const type = pieceCode[1]?.toUpperCase();
  if (!['w','b'].includes(color)) return null;
  if (!['P','R','N','B','Q','K'].includes(type)) return null;
  return color + type;
}

// ----------------------------
// Optional internal flat index helpers (kept for internal logic)
// ----------------------------

// Convert row/col to internal 0-63 index
export function rowColToIndex(row, col) {
  return row * 8 + col;
}

// Convert 0-63 index to row/col
export function indexToRowCol(index) {
  const row = Math.floor(index / 8);
  const col = index % 8;
  return { row, col };
}

// Convert 0-63 index to coordinate
export function indexToCoord(index) {
  const { row, col } = indexToRowCol(index);
  return coordsToCoord(row, col);
}

// Convert coordinate to 0-63 index
export function coordToIndex(coord) {
  const { row, col } = coordToRowCol(coord);
  return rowColToIndex(row, col);
}
