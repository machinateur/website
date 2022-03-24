/*
 * MIT License
 *
 * Copyright (c) 2021-2022 machinateur
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

// build-coverage.js script to extract used/unused rules to defer non-critical css
//  Licensed under MIT (https://github.com/machinateur/website/blob/main/LICENSE)
//  More information https://web.dev/defer-non-critical-css/
//  and https://github.com/puppeteer/puppeteer/blob/main/docs/api.md#class-coverage

const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
    const browser = await puppeteer.launch({
        ignoreHTTPSErrors: true,
    });
    const page = await browser.newPage();
    page.setJavaScriptEnabled(false);

    let sitemap = [
        'https://127.0.0.1:1312/',
    ];

    if (fs.existsSync('./var/build/coverage-sitemap.txt')) {
        const buffer = fs.readFileSync('./var/build/coverage-sitemap.txt');

        sitemap = buffer.toString()
            .split("\n");
    }
    for (const url of sitemap) {
        if (0 === url.length) {
            // in case of an empty line,
            continue;
        }

        await Promise.all([
            page.coverage.startCSSCoverage()
        ]);

        console.log(`Visiting ${url}...`);

        await page.goto(url);

        const coverageIterator = await Promise.all([
            page.coverage.stopCSSCoverage(),
        ]);

        const coverage = [...coverageIterator];

        let bytes_used = 0;
        let bytes_total = 0;
        let covered_code = '';

        for (const entry of coverage[0]) {
            bytes_total += entry.text.length;

            console.log(`Total Bytes for ${entry.url}: ${entry.text.length}.`);

            for (const range of entry.ranges) {
                bytes_used += range.end - range.start - 1;

                covered_code += entry.text.slice(range.start, range.end) + "\n";
            }
        }

        console.log(`Total Bytes: ${bytes_total}.`);
        console.log(`Used Bytes: ${bytes_used}.`);

        const path = (new URL(url)).pathname;
        fs.writeFile('./templates/style/' + path + '.css', covered_code, function (err) {
            if (err) {
                console.log(err);

                return;
            }

            console.log('Done!');
        });
    }

    await browser.close();
})();
