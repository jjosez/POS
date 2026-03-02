/*window.addEventListener("click", function (event) {
    let menu = getElement('navbarMenu');

    /!*if (!menu.contains(event.target) && !menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    }*!/
})*/

/**
 * Función para alternar la visibilidad de un elemento.
 * @param {HTMLElement} target - El elemento que se debe alternar.
 */
function toggleVisibility(target) {
    if (!target) return;
    target.classList.toggle('hidden');
}

/**
 * Función para manejar la lógica de un "collapse".
 * @param {HTMLElement} element - El elemento que activa el colapso.
 */
export function toggleCollapse(element) {
    const target = document.getElementById(element.dataset.target);
    const elementOntoggle = document.getElementById(element.dataset.ontoggle);

    toggleVisibility(target);
    if (elementOntoggle) toggleVisibility(elementOntoggle);
}

/**
 * Función para manejar la lógica de un "block".
 * @param {HTMLElement} element - El elemento que activa el cambio de bloque.
 */
export function toggleBlock(element) {
    const target = document.getElementById(element.dataset.target);
    const elementOntoggle = document.getElementById(element.dataset.ontoggle);

    toggleVisibility(target);
    if (elementOntoggle) toggleVisibility(elementOntoggle);
}

/**
 * Función para manejar el cambio de pestañas (tab).
 * @param {HTMLElement} element - El elemento que activa el cambio de pestaña.
 */
const toggleTab = element => {
    const target = document.getElementById(element.dataset.target);
    const tabList = element.closest('.tablist');
    const tabsContainer = document.getElementById(tabList.dataset.target);

    if (!target || !tabsContainer) return;

    // Ocultar todas las pestañas
    tabsContainer.querySelectorAll('.tabcontent').forEach(tabContent => {
        tabContent.style.display = 'none';
    });

    // Eliminar la clase 'tab-active' de todas las pestañas
    tabList.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('tab-active');
    });

    // Mostrar la pestaña activa y marcarla como activa
    target.style.display = 'block';
    element.classList.add('tab-active');
};

/**
 * Manejador de eventos para los diferentes toggles.
 * Usamos un objeto para mapear el tipo de toggle a su respectiva función.
 */
const eventHandler = element => {
    const toggleType = element.dataset.toggle;
    const toggleActions = {
        'collapse': toggleCollapse,
        'tab': toggleTab,
        'block': toggleBlock
    };

    const action = toggleActions[toggleType];
    if (action) {
        action(element);
    } else {
        toggleVisibility(document.getElementById(element.dataset.target));
    }
};

document.addEventListener('click', event => {
    const element = event.target.closest('[data-toggle]');
    if (!element) return;

    const toggleType = element.dataset.toggle;
    if (toggleType === 'modal') return;

    eventHandler(element);
}, false);
