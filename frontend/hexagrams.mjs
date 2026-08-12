// hexagrams.mjs

// Explicit mapping from chess coordinate → hexagram number + keyword
export const hexagrams = {
  a8: { hexNum: 49, keyword: 'Communication' },
  b8: { hexNum: 50, keyword: 'Recollection' },
  c8: { hexNum: 52, keyword: 'Self-Sufficiency' },
  d8: { hexNum: 55, keyword: 'Nonspecialization' },
  e8: { hexNum: 48, keyword: 'Internalization' },
  f8: { hexNum: 51, keyword: 'Concentration' },
  g8: { hexNum: 53, keyword: 'Disorientation' },
  h8: { hexNum: 54, keyword: 'Extinction' },
  a7: { hexNum: 41, keyword: 'Gratification' },
  b7: { hexNum: 42, keyword: 'Irrationality' },
  c7: { hexNum: 44, keyword: 'Improvisation' },
  d7: { hexNum: 47, keyword: 'Synchronization' },
  e7: { hexNum: 40, keyword: 'Confrontation' },
  f7: { hexNum: 43, keyword: 'Nonsublimation' },
  g7: { hexNum: 45, keyword: 'Uncertainty' },
  h7: { hexNum: 46, keyword: 'Specialization' },
  a6: { hexNum: 25, keyword: 'Persuasion' },
  b6: { hexNum: 26, keyword: 'Ambition' },
  c6: { hexNum: 28, keyword: 'Regeneration' },
  d6: { hexNum: 31, keyword: 'Transcendence' },
  e6: { hexNum: 24, keyword: 'Participation' },
  f6: { hexNum: 27, keyword: 'Provocation' },
  g6: { hexNum: 29, keyword: 'Eradication' },
  h6: { hexNum: 30, keyword: 'Evolution' },
  a5: { hexNum: 1, keyword: 'Revelation' },
  b5: { hexNum: 2, keyword: 'Intuition' },
  c5: { hexNum: 4, keyword: 'Identification' },
  d5: { hexNum: 7, keyword: 'Orientation' },
  e5: { hexNum: 0, keyword: 'Essence' },
  f5: { hexNum: 3, keyword: 'Emanation' },
  g5: { hexNum: 5, keyword: 'Interpretation' },
  h5: { hexNum: 6, keyword: 'Cultivation' },
  a4: { hexNum: 57, keyword: 'Celebration' },
  b4: { hexNum: 58, keyword: 'Compliance' },
  c4: { hexNum: 60, keyword: 'Reintegration' },
  d4: { hexNum: 63, keyword: 'Existence' },
  e4: { hexNum: 56, keyword: 'Recapitulation' },
  f4: { hexNum: 59, keyword: 'Inspiration' },
  g4: { hexNum: 61, keyword: 'Habituation' },
  h4: { hexNum: 62, keyword: 'Disintegration' },
  a3: { hexNum: 33, keyword: 'Compulsion' },
  b3: { hexNum: 34, keyword: 'Reflection' },
  c3: { hexNum: 36, keyword: 'Resilience' },
  d3: { hexNum: 39, keyword: 'Individuation' },
  e3: { hexNum: 32, keyword: 'Experience' },
  f3: { hexNum: 35, keyword: 'Propagation' },
  g3: { hexNum: 37, keyword: 'Potentiality' },
  h3: { hexNum: 38, keyword: 'Utilization' },
  a2: { hexNum: 17, keyword: 'Congruity' },
  b2: { hexNum: 18, keyword: 'Certainty' },
  c2: { hexNum: 20, keyword: 'Tradition' },
  d2: { hexNum: 23, keyword: 'Noninterference' },
  e2: { hexNum: 16, keyword: 'Conceptualization' },
  f2: { hexNum: 19, keyword: 'Instruction' },
  g2: { hexNum: 21, keyword: 'Rationality' },
  h2: { hexNum: 22, keyword: 'Preconception' },
  a1: { hexNum: 9, keyword: 'Sensitization' },
  b1: { hexNum: 10, keyword: 'Incongruity' },
  c1: { hexNum: 12, keyword: 'Authenticity' },
  d1: { hexNum: 15, keyword: 'Emancipation' },
  e1: { hexNum: 8, keyword: 'Devotion' },
  f1: { hexNum: 11, keyword: 'Projection' },
  g1: { hexNum: 13, keyword: 'Induction' },
  h1: { hexNum: 14, keyword: 'Inhibition' },
};

// Helper: get row/col from hexNum
export function getRowColFromHex(hexNum) {
  for (const [coord, { hexNum: hn }] of Object.entries(hexagrams)) {
    if (hn === hexNum) {
      const file = coord[0];
      const rank = coord[1];
      const col = file.charCodeAt(0) - 97; // a=0
      const row = 8 - parseInt(rank);        // rank 8=0
      return { row, col };
    }
  }
  return null;
}

// Helper: get hexNum from row/col
export function getHexFromRowCol(row, col) {
  const file = String.fromCharCode(97 + col);
  const rank = 8 - row;
  const coord = `${file}${rank}`;
  return hexagrams[coord] ? hexagrams[coord].hexNum : null;
}

// Helper: get keyword from hexagram number
export function getKeywordFromHex(hexNum) {
  for (const [coord, { hexNum: hn, keyword }] of Object.entries(hexagrams)) {
    if (hn === hexNum) {
      return keyword;
    }
  }
  return '';
}
