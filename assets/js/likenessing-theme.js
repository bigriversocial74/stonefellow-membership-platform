(function () {
  'use strict';
  const root = document.body;
  if (!root || !root.classList.contains('likenessing-theme')) return;

  const replacements = [
    [/Stonefellow Streaming/gi, 'Likenessing Streaming'],
    [/Stonefellow soundtrack/gi, 'Likenessing soundtrack'],
    [/Stonefellow series/gi, 'Likenessing series'],
    [/Stonefellow band/gi, 'Likenessing cast'],
    [/Stonefellow brothers/gi, 'Likenessing characters'],
    [/Stonefellow/gi, 'Likenessing']
  ];

  function replaceText(value) {
    let next = value;
    replacements.forEach(function (entry) { next = next.replace(entry[0], entry[1]); });
    return next;
  }

  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
    acceptNode: function (node) {
      const parent = node.parentElement;
      if (!parent || /^(SCRIPT|STYLE|NOSCRIPT|TEXTAREA|CODE|PRE)$/i.test(parent.tagName)) return NodeFilter.FILTER_REJECT;
      return /Stonefellow/i.test(node.nodeValue || '') ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
    }
  });
  const textNodes = [];
  while (walker.nextNode()) textNodes.push(walker.currentNode);
  textNodes.forEach(function (node) { node.nodeValue = replaceText(node.nodeValue || ''); });

  document.querySelectorAll('[alt],[title],[aria-label],[placeholder]').forEach(function (element) {
    ['alt', 'title', 'aria-label', 'placeholder'].forEach(function (attribute) {
      if (!element.hasAttribute(attribute)) return;
      const value = element.getAttribute(attribute) || '';
      if (/Stonefellow/i.test(value)) element.setAttribute(attribute, replaceText(value));
    });
  });

  document.title = replaceText(document.title);
})();
