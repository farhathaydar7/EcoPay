// popup.js
document.addEventListener('DOMContentLoaded', () => {
    // Example: Assume you have a button with ID 'popupButton' in your popup HTML.
    const popupButton = document.getElementById('popupButton');
    
    if (popupButton) {
      popupButton.addEventListener('click', () => {
        console.log("Popup button clicked.");
        // Check if chrome.tabs and executeScript are available before calling.
        if (chrome && chrome.tabs && chrome.tabs.executeScript) {
          chrome.tabs.executeScript({
            code: 'console.log("Injected script executed");'
          });
        } else {
          console.error("chrome.tabs.executeScript is not available in this context.");
        }
      });
    } else {
      console.error("Element with id 'popupButton' not found in the DOM.");
    }
  });
  