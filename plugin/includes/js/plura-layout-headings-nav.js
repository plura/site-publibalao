export function createTreeNavigation({ target = document.body, holder = document.body, threshold = 0.5, highestHeading = 'h2' } = {}) {
    // Calculate the level of the highest heading (e.g., h2 -> 2)
    const highestHeadingLevel = parseInt(highestHeading.replace('h', ''));

    // Create the tree container
    const treeContainer = document.createElement('div');
    treeContainer.className = 'plura-layout-heading-tree-nav-container';

    // Find all the headings starting from the highestHeadingLevel downwards
    const headings = target.querySelectorAll(`h${highestHeadingLevel}, h${highestHeadingLevel + 1}, h${highestHeadingLevel + 2}, h${highestHeadingLevel + 3}, h${highestHeadingLevel + 4}`);

    const createList = () => {
        const list = document.createElement('ul');
        list.className = 'plura-layout-heading-tree-nav-list';
        return list;
    };

    const headingMap = new Map(); // Maps headings to their corresponding list items

    // Open lists, each with the level it was opened for. The root takes any level, so
    // content that starts at an h3, or skips a level, still has somewhere to go.
    const listStack = [{ list: treeContainer.appendChild(createList()), level: 0 }];

    headings.forEach(heading => {
        const level = parseInt(heading.tagName.substring(1)); // Extract number from H2, H3, etc.

        const listItem = document.createElement('li');
        listItem.className = 'plura-layout-heading-tree-nav-item';
        listItem.setAttribute('data-plura-layout-heading-nav-item-level', level); // Set the data attribute for heading level

        const link = document.createElement('a');
        link.className = 'plura-layout-heading-tree-nav-link';
        link.textContent = heading.textContent;
        link.href = `#${heading.id || heading.textContent.toLowerCase().replace(/\s+/g, '-')}`;

        // Add id to the heading if it doesn't have one for linking
        if (!heading.id) {
            heading.id = heading.textContent.toLowerCase().replace(/\s+/g, '-');
        }

        // Store the mapping between the heading and its list item
        headingMap.set(heading, listItem);

        listItem.appendChild(link);

        // Close lists opened for deeper headings (e.g., H3 -> H2)
        while (listStack.length > 1 && level < listStack.at(-1).level) {
            listStack.pop();
        }

        // Nest only under an item that is actually shallower: a missing or same-level
        // previous item is a sibling, not a parent
        let { list } = listStack.at(-1);
        const parentItem = list.lastElementChild;

        if (parentItem && Number(parentItem.dataset.pluraLayoutHeadingNavItemLevel) < level) {
            list = parentItem.querySelector(':scope > ul') ?? parentItem.appendChild(createList());
            listStack.push({ list, level });
        }

        list.appendChild(listItem);

        // Add click event listener to the link to activate and scroll
        link.addEventListener('click', function (e) {
            e.preventDefault();
            activate(listItem, true); // Trigger activation with scroll on click
        });
    });

    // Append the tree to the holder
    holder.appendChild(treeContainer);

    // Function to activate the selected item and its ancestors
    function activate(listItem, scroll = false) {
        // Deactivate all currently active items with a single query selector
        document.querySelectorAll('.plura-layout-heading-tree-nav-active, .plura-layout-heading-tree-nav-active-parent, .plura-layout-heading-tree-nav-active-ancestor').forEach(item => {
            item.classList.remove('plura-layout-heading-tree-nav-active', 'plura-layout-heading-tree-nav-active-parent', 'plura-layout-heading-tree-nav-active-ancestor');
        });

        // Activate the clicked item
        listItem.classList.add('plura-layout-heading-tree-nav-active');

        // Activate ancestors (traverse upwards to the root)
        let parent = listItem.parentElement;
        let isImmediateParent = true; // Flag for immediate parent

        while (parent && parent !== treeContainer) {
            if (parent.tagName === 'LI') {
                if (isImmediateParent) {
                    parent.classList.add('plura-layout-heading-tree-nav-active-parent');
                    isImmediateParent = false;
                } else {
                    parent.classList.add('plura-layout-heading-tree-nav-active-ancestor');
                }
            }
            parent = parent.parentElement;
        }

        // Scroll to the corresponding heading if triggered by click (scroll = true)
        if (scroll) {
            const targetId = listItem.querySelector('a').getAttribute('href').substring(1);
            const targetHeading = document.getElementById(targetId);
            if (targetHeading) {
                targetHeading.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

    // IntersectionObserver callback to detect visibility changes
    function handleIntersection(entries) {
        entries.forEach(entry => {
            const listItem = headingMap.get(entry.target);

            if (entry.isIntersecting) {
                activate(listItem); // Activate the corresponding navigation item without scrolling
            }
        });
    }

    // Create an IntersectionObserver to track heading visibility
    const observer = new IntersectionObserver(handleIntersection, {
        root: null, // Use the viewport as the root
        threshold: threshold // Default threshold for when an item is considered "visible"
    });

    // Observe each heading
    headings.forEach(heading => {
        observer.observe(heading);
    });
}

// Usage example:
// createTreeNavigation({ target: document.querySelector('.content'), holder: document.querySelector('.menu-holder'), highestHeading: 'h2', threshold: 0.6 });
