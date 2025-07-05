import {Eta} from "../../vendor/eta/browser.module.js?v=3.5.0";

const eta =  new Eta({ useWith: true });
let instance;

class TemplateManager {
    constructor(templateMap = {}) {
        if (instance) throw new Error("TemplateManager instance error.");
        instance = this;

        this.templates = templateMap; // Plantillas cargadas (nombre → HTML)
        this.viewCache = {};          // Cache de nodos DOM (nombre → elemento)
    }

    /**
     * Asignar plantilla manualmente
     */
    registerTemplate(name, htmlString) {
        this.templates[name] = htmlString;
    }

    /**
     * Carga plantillas al inicializar
     */
    preloadTemplatesFromDOM(prefix = '') {
        const scriptTemplates = document.querySelectorAll('script[type="text/template"]');

        scriptTemplates.forEach((tpl) => {
            const id = tpl.id.replace(prefix, '');
            this.templates[id] = tpl.innerHTML;
        });
    }

    /**
     * Renderiza una plantilla con datos en un contenedor
     * @param {string} templateName - clave del template
     * @param {object} data - datos para renderizar
     * @param {HTMLElement|string} container - contenedor DOM o id
     */
    render(templateName, data = {}, container) {
        const template = this.templates[templateName];
        if (!template) {
            console.error(`❌ Template "${templateName}" not found.`);
            return;
        }

        let target = container;
        if (typeof container === 'string') {
            target = this.viewCache[container] || document.getElementById(container);
            if (target) this.viewCache[container] = target;
        }

        if (!target) {
            console.error(`❌ Container for "${templateName}" not found.`);
            return;
        }

        target.innerHTML = eta.renderString(template, data);
    }

    /**
     * Renderiza y devuelve el HTML
     */
    renderToString(templateName, data = {}) {
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
