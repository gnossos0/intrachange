// Board layout module for Intrachange

// Optional: import helper functions or constants if needed
// import { someHelper } from './utils.mjs';

// This file contains rank arrays from 1 to 8, each representing a row on the board

// Dark/light square convention: true = dark, false = light
// Coordinates follow standard chess notation: a1..h8

export const rank8 = [
  { coord: 'a8', hexNum: 49, keyword: 'Communication', dark: true },
  { coord: 'b8', hexNum: 50, keyword: 'Recollection', dark: false },
  { coord: 'c8', hexNum: 52, keyword: 'Self-Sufficiency', dark: true },
  { coord: 'd8', hexNum: 55, keyword: 'Nonspecialization', dark: false },
  { coord: 'e8', hexNum: 48, keyword: 'Internalization', dark: true },
  { coord: 'f8', hexNum: 51, keyword: 'Concentration', dark: false },
  { coord: 'g8', hexNum: 53, keyword: 'Disorientation', dark: true },
  { coord: 'h8', hexNum: 54, keyword: 'Extinction', dark: false }
];
export const rank7 = [
  { coord: 'a7', hexNum: 41, keyword: 'Gratification', dark: false },
  { coord: 'b7', hexNum: 42, keyword: 'Irrationality', dark: true },
  { coord: 'c7', hexNum: 44, keyword: 'Improvisation', dark: false },
  { coord: 'd7', hexNum: 47, keyword: 'Synchronization', dark: true },
  { coord: 'e7', hexNum: 40, keyword: 'Confrontation', dark: false },
  { coord: 'f7', hexNum: 43, keyword: 'Sublimation', dark: true },
  { coord: 'g7', hexNum: 45, keyword: 'Uncertainty', dark: false },
  { coord: 'h7', hexNum: 46, keyword: 'Specialization', dark: true }
];
export const rank6 = [
  { coord: 'a6', hexNum: 25, keyword: 'Persuasion', dark: true },
  { coord: 'b6', hexNum: 26, keyword: 'Ambition', dark: false },
  { coord: 'c6', hexNum: 28, keyword: 'Regeneration', dark: true },
  { coord: 'd6', hexNum: 31, keyword: 'Transcendence', dark: false },
  { coord: 'e6', hexNum: 24, keyword: 'Participation', dark: true },
  { coord: 'f6', hexNum: 27, keyword: 'Provocation', dark: false },
  { coord: 'g6', hexNum: 29, keyword: 'Eradication', dark: true },
  { coord: 'h6', hexNum: 30, keyword: 'Evolution', dark: false }
];
export const rank5 = [
  { coord: 'a5', hexNum: 1, keyword: 'Revelation', dark: false },
  { coord: 'b5', hexNum: 2, keyword: 'Intuition', dark: true },
  { coord: 'c5', hexNum: 4, keyword: 'Identification', dark: false },
  { coord: 'd5', hexNum: 7, keyword: 'Orientation', dark: true },
  { coord: 'e5', hexNum: 0, keyword: 'Essence', dark: false },
  { coord: 'f5', hexNum: 3, keyword: 'Emanation', dark: true },
  { coord: 'g5', hexNum: 5, keyword: 'Interpretation', dark: false },
  { coord: 'h5', hexNum: 6, keyword: 'Cultivation', dark: true }
];
export const rank4 = [
  { coord: 'a4', hexNum: 57, keyword: 'Celebration', dark: true },
  { coord: 'b4', hexNum: 58, keyword: 'Compliance', dark: false },
  { coord: 'c4', hexNum: 60, keyword: 'Reintegration', dark: true },
  { coord: 'd4', hexNum: 63, keyword: 'Existence', dark: false },
  { coord: 'e4', hexNum: 56, keyword: 'Recapitulation', dark: true },
  { coord: 'f4', hexNum: 59, keyword: 'Inspiration', dark: false },
  { coord: 'g4', hexNum: 61, keyword: 'Habituation', dark: true },
  { coord: 'h4', hexNum: 62, keyword: 'Disintegration', dark: false }
];
export const rank3 = [
  { coord: 'a3', hexNum: 33, keyword: 'Compulsion', dark: false },
  { coord: 'b3', hexNum: 34, keyword: 'Reflection', dark: true },
  { coord: 'c3', hexNum: 36, keyword: 'Resilience', dark: false },
  { coord: 'd3', hexNum: 39, keyword: 'Individuation', dark: true },
  { coord: 'e3', hexNum: 32, keyword: 'Experience', dark: false },
  { coord: 'f3', hexNum: 35, keyword: 'Propagation', dark: true },
  { coord: 'g3', hexNum: 37, keyword: 'Potentiality', dark: false },
  { coord: 'h3', hexNum: 38, keyword: 'Utilization', dark: true }
];
export const rank2 = [
  { coord: 'a2', hexNum: 17, keyword: 'Congruity', dark: true },
  { coord: 'b2', hexNum: 18, keyword: 'Certainty', dark: false },
  { coord: 'c2', hexNum: 20, keyword: 'Tradition', dark: true },
  { coord: 'd2', hexNum: 23, keyword: 'Noninterference', dark: false },
  { coord: 'e2', hexNum: 16, keyword: 'Conceptualization', dark: true },
  { coord: 'f2', hexNum: 19, keyword: 'Instruction', dark: false },
  { coord: 'g2', hexNum: 21, keyword: 'Rationality', dark: true },
  { coord: 'h2', hexNum: 22, keyword: 'Preconception', dark: false }
];
export const rank1 = [
  { coord: 'a1', hexNum: 9, keyword: 'Sensitization', dark: false },
  { coord: 'b1', hexNum: 10, keyword: 'Incongruity', dark: true },
  { coord: 'c1', hexNum: 12, keyword: 'Authenticity', dark: false },
  { coord: 'd1', hexNum: 15, keyword: 'Emancipation', dark: true },
  { coord: 'e1', hexNum: 8, keyword: 'Devotion', dark: false },
  { coord: 'f1', hexNum: 11, keyword: 'Projection', dark: true },
  { coord: 'g1', hexNum: 13, keyword: 'Induction', dark: false },
  { coord: 'h1', hexNum: 14, keyword: 'Inhibition', dark: true }
];
<script type="module" src="./boardController.mjs"></script>
<script type="module" src="./script.mjs"></script>

