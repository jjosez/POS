const colors = require('tailwindcss/colors')

module.exports = {
    content: [
        './View/**/*.*.twig',
        './View/Block/POS/*.*.twig',
        './View/Modal/POS/*.*.twig'
    ],
    safelist: [
        'modal-backdrop',
        'selected',
        'd-none',
        'form-control',
        {
            pattern: /bg-(red|green|blue|yellow)-(100|200|500)/,
            variants: ['hover', 'even', 'odd'],
        },
    ],
    theme: {
        extend: {
            colors: {
                'slate': {
                    '50': '#f7f9f9',
                    '100': '#eff2f3',
                    '200': '#d7dfe2',
                    '300': '#bfcbd1',
                    '400': '#90a4ae',
                    '500': '#607d8b',
                    '600': '#56717d',
                    '700': '#485e68',
                    '800': '#3a4b53',
                    '900': '#2f3d44'
                }
            }
        },
        maxHeight: {
            '0': '0',
            '1/4': '25%',
            '1/2': '50%',
            '3/4': '75%',
            '4/5': '80%',
            'full': '100%',
        },
        minHeight: {
            '0': '0',
            '1/4': '25%',
            '1/2': '50%',
            '3/4': '75%',
            '4/5': '80%',
            'full': '100%',
        }
    }
}
