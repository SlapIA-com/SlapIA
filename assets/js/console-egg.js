/**
 * Console Easter Egg
 * A hidden message for developers who inspect the code.
 */

(function () {
    const styleTitle = 'font-size: 40px; font-weight: bold; color: #7000ff; text-shadow: 2px 2px 0px #000; font-family: "Courier New", monospace;';
    const styleBody = 'font-size: 14px; color: #aaa; font-family: sans-serif; line-height: 1.5;';
    const styleLink = 'font-size: 14px; color: #0dcaf0; text-decoration: underline; cursor: pointer;';

    console.log('%cSlapIA', styleTitle);
    console.log(
        '%cYou found the entrance. We like curious people.\n' +
        'This site was built with PHP, Vanilla JS, and a lot of passion.\n' +
        'No Wordpress was harmed in the making of this website.\n\n' +
        'Want to talk code or AI? Reach out.',
        styleBody
    );
    console.log('%c-> thomas25.lapierre@outlook.com', styleLink);
})();
