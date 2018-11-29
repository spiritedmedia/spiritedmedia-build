import { escAttr } from 'utils';
import getIconSVG from 'getIconSVG';

const Twig = window.Twig;

// Set up the `ped_icon()` Twig function
Twig.extendFunction('ped_icon', getIconSVG);

Twig.extendFilter('esc_attr', s => escAttr(s));
Twig.extendFilter('esc_url', s => escape(s));

export default Twig.twig;
