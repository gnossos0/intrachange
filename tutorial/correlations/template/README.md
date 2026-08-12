# Correlation Module Template

This template provides a standardized structure for creating correlation modules within the Intrachange tutorial system. Each correlation module focuses on a specific pattern (1::2, 2::2, 4::4, 6::6, 8::8) and teaches players how to recognize and utilize these correlations in gameplay.

## Template Structure

```
template/
├── index.html         # Main module interface with navigation
├── content.json       # Move data with hexagrams and explanations
├── module.js          # Logic for content rendering and navigation
├── module.css         # Styling for responsive layout
└── README.md         # This documentation
```

## Creating a New Module

To create a new correlation module (e.g., 2-2 for the 2::2 pattern):

1. **Copy the template folder**:
   ```bash
   cp -r tutorial/correlations/template tutorial/correlations/2-2
   ```

2. **Update content.json**:
   - Replace the example moves with actual correlation data
   - Each move should include:
     - `notation`: Chess notation (e.g., "e4")
     - `hexagram`: Corresponding I-Ching hexagram
     - `coordinate`: Board position
     - `keywords`: Array of correlation themes
     - `interpretation`: Explanation of the correlation

3. **Customize index.html**:
   - Update the page title and header text
   - Modify the back button link to point to the correlations index
   - Ensure proper meta tags and descriptions

4. **Adjust module.css** (if needed):
   - The template CSS should work for most modules
   - Only modify if the specific pattern requires unique styling

5. **Update module.js** (if needed):
   - The template JavaScript should handle standard correlation viewing
   - Only modify if special interaction patterns are needed

## Content Format

The `content.json` file follows this structure:

```json
{
  "title": "Pattern Name (e.g., 4::4 Correlations)",
  "description": "Brief description of the pattern",
  "moves": [
    {
      "notation": "e4",
      "hexagram": "Hexagram Name",
      "coordinate": "e4",
      "keywords": ["keyword1", "keyword2", "keyword3", "keyword4"],
      "interpretation": "Detailed explanation of this move's correlation significance..."
    }
  ]
}
```

## Integration Steps

After creating a new module:

1. **Add to navigation**: Update the main correlations index to include a link to the new module
2. **Test functionality**: Verify navigation, content loading, and responsive behavior
3. **Validate content**: Ensure all hexagram references and correlations are accurate

## Shared Dependencies

All correlation modules automatically inherit:
- **Utilities**: `../shared/correlations-utils.js` for DOM helpers and formatting
- **Events**: `../shared/correlations-events.js` for interaction handling  
- **Logic**: `../shared/correlation-logic.js` for correlation computations
- **Styling**: `../shared/correlations.css` for component styles

## Design Principles

- **Consistency**: All modules follow the same navigation and layout patterns
- **Responsiveness**: Mobile-first design with clean breakpoints
- **Accessibility**: Proper semantic HTML and keyboard navigation support
- **Performance**: Efficient JSON loading and DOM manipulation
- **Modularity**: Clean separation between template structure and content data

## File Dependencies

```
index.html
├── module.css (local styling)
├── module.js (content logic)
├── ../shared/correlations.css (component styles)
├── ../shared/correlations-utils.js (utilities)
├── ../shared/correlations-events.js (event handling)
└── ../shared/correlation-logic.js (correlation logic)
```

This template enables rapid creation of consistent, high-quality correlation modules while maintaining the surgical development approach required by the project constraints.