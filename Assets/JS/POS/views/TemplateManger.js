import {Eta} from "../../vendor/eta/dist/core.js?v4.6";

const eta =  new Eta({ useWith: true });
let instance;

class TemplateManager {
    constructor(templateMap = {})
    {
        if (instance) {
            throw new Error("TemplateManager instance error.");
        }
        instance = this;

        this.templates = templateMap;
        this.viewCache = {};

        this.preloadTemplatesFromDOM();
    }

    /**
     * Register a template manually
     */
    registerTemplate(name, htmlString)
    {
        this.templates[name] = htmlString;
    }

    /**
     * Loads templates on initialization
     */
    preloadTemplatesFromDOM(prefix = '')
    {
        const scriptTemplates = document.querySelectorAll('script[type="text/template"]');

        scriptTemplates.forEach((tpl) => {
            const id = tpl.id.replace(prefix, '');
            this.templates[id] = tpl.innerHTML;
        });
    }

    /**
     * Reloads a specific template from the DOM
     * @param {string} templateId
     */
    loadTemplate(templateId)
    {
        const templateElement = document.getElementById(templateId);

        if (templateElement && templateElement.type === 'text/template') {
            this.templates[templateId] = templateElement.innerHTML;
            return true;
        }
        return false;
    }

    /**
     * Renders a template with data into a container
     * @param {string} templateName - the template key
     * @param {object} data - data to render
     * @param {HTMLElement|string} container - DOM container or its id
     */
    render(templateName, data = {}, container)
    {
        let template = this.templates[templateName];

        if (!template) {
            const loaded = this.loadTemplate(templateName);
            if (loaded) {
                template = this.templates[templateName];
            } else {
                console.error(`❌ Template "${templateName}" not found.`);
                return;
            }
        }

        let target = container;
        if (typeof container === 'string') {
            target = this.viewCache[container] || document.getElementById(container);
            if (target) {
                this.viewCache[container] = target;
            }
        }

        if (!target) {
            console.error(`❌ Container for "${templateName}" not found.`);
            return;
        }

        target.innerHTML = eta.renderString(template, data);
    }

    /**
     * Renders and returns the HTML string
     */
    renderToString(templateName, data = {})
    {
        const template = this.templates[templateName];
        if (!template) {
            console.error(`❌ Template "${templateName}" no not found.`);
            return '';
        }
        return eta.renderString(template, data);
    }
}

const templateManager = Object.freeze(new TemplateManager({}));
export default templateManager;
