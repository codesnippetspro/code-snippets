import { Page, expect } from '@playwright/test';
import { 
  SELECTORS, 
  TIMEOUTS, 
  URLS, 
  MESSAGES, 
  SNIPPET_TYPES, 
  SNIPPET_LOCATIONS, 
  BUTTONS 
} from './constants';

export interface SnippetFormOptions {
  name: string;
  code: string;
  type?: keyof typeof SNIPPET_TYPES;
  location?: keyof typeof SNIPPET_LOCATIONS;
}

export class SnippetsTestHelper {
  constructor(private page: Page) {}

  /**
   * Navigate to the Code Snippets admin page
   */
  async navigateToSnippetsAdmin(): Promise<void> {
    await this.page.goto(URLS.SNIPPETS_ADMIN);
    await this.page.waitForLoadState('networkidle');
    await this.page.waitForSelector(SELECTORS.WPBODY_CONTENT, { timeout: TIMEOUTS.DEFAULT });
  }

  /**
   * Navigate to frontend
   */
  async navigateToFrontend(): Promise<void> {
    await this.page.goto(URLS.FRONTEND);
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Click the "Add New" button to start creating a snippet
   */
  async clickAddNewSnippet(): Promise<void> {
    await this.page.waitForSelector(SELECTORS.PAGE_TITLE, { timeout: TIMEOUTS.DEFAULT });
    await this.page.click(SELECTORS.ADD_NEW_BUTTON);
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Fill the snippet form with the provided options
   */
  async fillSnippetForm(options: SnippetFormOptions): Promise<void> {
    // Fill title
    await this.page.waitForSelector(SELECTORS.TITLE_INPUT);
    await this.page.fill(SELECTORS.TITLE_INPUT, options.name);

    // Select snippet type
    if (options.type && options.type !== 'PHP') {
      await this.page.click(SELECTORS.SNIPPET_TYPE_SELECT);
      await this.page.click(`text=${SNIPPET_TYPES[options.type]}`);
    }

    // Fill code
    await this.page.waitForSelector(SELECTORS.CODE_MIRROR_TEXTAREA);
    await this.page.fill(SELECTORS.CODE_MIRROR_TEXTAREA, options.code);

    // Select location
    if (options.location) {
      await this.page.waitForSelector(SELECTORS.LOCATION_SELECT, { timeout: TIMEOUTS.SHORT });
      await this.page.click(SELECTORS.LOCATION_SELECT);
      
      await this.page.waitForSelector(`text=${SNIPPET_LOCATIONS[options.location]}`, { timeout: TIMEOUTS.SHORT });
      await this.page.click(`text=${SNIPPET_LOCATIONS[options.location]}`, { force: true });
    }
  }

  /**
   * Save the snippet with the specified action
   */
  async saveSnippet(action: 'save' | 'save_and_activate' | 'save_and_deactivate' = 'save'): Promise<void> {
    const buttonMap = {
      save: BUTTONS.SAVE,
      save_and_activate: BUTTONS.SAVE_AND_ACTIVATE,
      save_and_deactivate: BUTTONS.SAVE_AND_DEACTIVATE,
    };

    await this.page.click(buttonMap[action]);
  }

  /**
   * Expect a success message with the specified text
   */
  async expectSuccessMessage(expectedMessage: string): Promise<void> {
    await expect(this.page.locator(SELECTORS.SUCCESS_MESSAGE)).toContainText(expectedMessage);
  }

  /**
   * Expect a success message in paragraph element
   */
  async expectSuccessMessageInParagraph(expectedMessage: string): Promise<void> {
    await expect(this.page.locator(SELECTORS.SUCCESS_MESSAGE_P)).toContainText(expectedMessage);
  }

  /**
   * Open an existing snippet by name
   */
  async openSnippet(snippetName: string): Promise<void> {
    await this.page.waitForSelector(`text=${snippetName}`);
    await this.page.click(`text=${snippetName}`);
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Delete a snippet (assumes you're already on the snippet edit page)
   */
  async deleteSnippet(): Promise<void> {
    await this.page.click(BUTTONS.DELETE);
    await this.page.click(SELECTORS.DELETE_CONFIRM_BUTTON);
  }

  /**
   * Check if a snippet exists on the snippets list page
   */
  async snippetExists(snippetName: string): Promise<boolean> {
    const count = await this.page.locator(`text=${snippetName}`).count();
    return count > 0;
  }

  /**
   * Clean up a snippet by name (navigate to admin, find snippet, delete it)
   */
  async cleanupSnippet(snippetName: string): Promise<void> {
    await this.navigateToSnippetsAdmin();
    
    if (await this.snippetExists(snippetName)) {
      await this.openSnippet(snippetName);
      await this.deleteSnippet();
    }
  }

  /**
   * Verify the current URL contains the snippets admin page
   */
  async expectToBeOnSnippetsAdminPage(): Promise<void> {
    const currentUrl = this.page.url();
    expect(currentUrl).toContain('page=snippets');
    await expect(this.page.locator(SELECTORS.PAGE_TITLE)).toBeVisible();
  }

  /**
   * Expect an element to be visible
   */
  async expectElementVisible(selector: string): Promise<void> {
    await expect(this.page.locator(selector)).toBeVisible();
  }

  /**
   * Expect an element to not be visible
   */
  async expectElementNotVisible(selector: string): Promise<void> {
    await expect(this.page.locator(selector)).not.toBeVisible();
  }

  /**
   * Expect an element to have a specific count
   */
  async expectElementCount(selector: string, expectedCount: number): Promise<void> {
    const count = await this.page.locator(selector).count();
    expect(count).toBe(expectedCount);
  }

  /**
   * Expect text to be visible on the page
   */
  async expectTextVisible(text: string): Promise<void> {
    await expect(this.page.locator(`text=${text}`)).toBeVisible();
  }

  /**
   * Expect text to not be visible on the page
   */
  async expectTextNotVisible(text: string): Promise<void> {
    await expect(this.page.locator('body')).not.toContainText(text);
  }

  /**
   * Create a complete snippet with save and activate
   */
  async createAndActivateSnippet(options: SnippetFormOptions): Promise<void> {
    await this.clickAddNewSnippet();
    await this.fillSnippetForm(options);
    await this.saveSnippet('save_and_activate');
    await this.expectSuccessMessage(MESSAGES.SNIPPET_CREATED_AND_ACTIVATED);
  }

  /**
   * Create a snippet without activating
   */
  async createSnippet(options: SnippetFormOptions): Promise<void> {
    await this.clickAddNewSnippet();
    await this.fillSnippetForm(options);
    await this.saveSnippet('save');
    await this.expectSuccessMessage(MESSAGES.SNIPPET_CREATED);
  }
}
