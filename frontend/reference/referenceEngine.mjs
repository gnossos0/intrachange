import { hexagramReference } from "./referenceData.mjs";

export function getHexagramReference(hexNumber) {
  return hexagramReference[hexNumber] || null;
}