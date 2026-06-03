const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'http://127.0.0.1:8000';
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots');

// Ensure screenshot directory exists
if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

// Test result tracker
const results = [];

function record(viewport, test, passed, detail = '') {
  results.push({ viewport, test, passed, detail });
  const status = passed ? 'PASS' : 'FAIL';
  console.log(`  [${status}] ${test}${detail ? ' - ' + detail : ''}`);
}

async function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function testViewport(viewport) {
  console.log(`\n${'='.repeat(60)}`);
  console.log(`Testing: ${viewport.name} (${viewport.width}x${viewport.height})`);
  console.log('='.repeat(60));

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
  });
  const page = await context.newPage();

  try {
    // ============================================================
    // TEST 1: Landing page loads correctly
    // ============================================================
    console.log('\n--- Landing Page Structure ---');

    try {
      const startTime = Date.now();
      await page.goto(BASE_URL, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await delay(2000); // Wait for JS rendering
      const loadTime = Date.now() - startTime;

      // Test 9: Page load time < 5s
      record(viewport.name, 'Page load time < 5s', loadTime < 5000, `${loadTime}ms`);

      // Logo visible
      const logoVisible = await page.locator('img[alt="Logo E-Monev"]').first().isVisible().catch(() => false);
      record(viewport.name, 'Logo is visible', logoVisible);

      // Hero heading contains "E-MONEV"
      const heroText = await page.locator('h1').first().innerText().catch(() => '');
      record(viewport.name, 'Hero heading contains "E-MONEV"', heroText.includes('E-MONEV'), `Found: "${heroText.substring(0, 50)}..."`);

      // "Ayo Mulai" button present
      const ayoMulaiVisible = await page.getByRole('link', { name: 'Ayo Mulai' }).isVisible().catch(() => false);
      record(viewport.name, '"Ayo Mulai" button is present', ayoMulaiVisible);

      // 3-step info cards
      const stepCards = await page.locator('section:has(h3:has-text("Registrasi")) h3').count().catch(() => 0);
      const hasRegistrasi = stepCards > 0 || await page.getByText('Registrasi PPID Pelaksana').isVisible().catch(() => false);
      const hasKuisioner = await page.getByText('Pengisian Kuisioner').isVisible().catch(() => false);
      const hasVerifikasi = await page.getByText('Verifikasi Kuisioner').isVisible().catch(() => false);
      record(viewport.name, 'Step card: Registrasi', hasRegistrasi);
      record(viewport.name, 'Step card: Kuisioner', hasKuisioner);
      record(viewport.name, 'Step card: Verifikasi', hasVerifikasi);

      // Navigation links
      const navBeranda = await page.getByRole('link', { name: 'Beranda' }).isVisible().catch(() => false);
      const navAlur = await page.getByRole('link', { name: 'Alur Kerja' }).isVisible().catch(() => false);
      const navStatistik = await page.getByRole('link', { name: 'Statistik' }).isVisible().catch(() => false);
      const navKontak = await page.getByRole('link', { name: 'Kontak' }).isVisible().catch(() => false);
      record(viewport.name, 'Nav: Beranda', navBeranda);
      record(viewport.name, 'Nav: Alur Kerja', navAlur);
      record(viewport.name, 'Nav: Statistik', navStatistik);
      record(viewport.name, 'Nav: Kontak', navKontak);

      // Footer contact info
      const emailVisible = await page.getByText('dinkominfobnakab@gmail.com').isVisible().catch(() => false);
      const whatsappVisible = await page.getByText('(+62) 812 1503 4540').isVisible().catch(() => false);
      record(viewport.name, 'Footer: email visible', emailVisible);
      record(viewport.name, 'Footer: WhatsApp visible', whatsappVisible);

      // Screenshot
      await page.screenshot({ path: path.join(SCREENSHOT_DIR, `${viewport.name.toLowerCase()}-landing.png`), fullPage: true });
    } catch (err) {
      record(viewport.name, 'Landing page tests', false, err.message);
    }

    // ============================================================
    // TEST 2: Login page loads
    // ============================================================
    console.log('\n--- Login Page ---');

    try {
      await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await delay(2000);

      // Email field
      const emailField = await page.locator('#email').isVisible().catch(() => false);
      record(viewport.name, 'Login: email field exists', emailField);

      // Password field
      const passwordField = await page.locator('#password').isVisible().catch(() => false);
      record(viewport.name, 'Login: password field exists', passwordField);

      // Submit button
      const submitBtn = await page.getByRole('button', { name: 'Masuk' }).isVisible().catch(() => false);
      record(viewport.name, 'Login: submit button exists', submitBtn);

      // Register link
      const registerLink = await page.getByRole('link', { name: 'Daftar' }).isVisible().catch(() => false);
      record(viewport.name, 'Login: register link exists', registerLink);

      await page.screenshot({ path: path.join(SCREENSHOT_DIR, `${viewport.name.toLowerCase()}-login.png`), fullPage: true });
    } catch (err) {
      record(viewport.name, 'Login page tests', false, err.message);
    }

    // ============================================================
    // TEST 3: Mobile-specific layout checks
    // ============================================================
    console.log('\n--- Layout / Responsiveness ---');

    try {
      await page.goto(BASE_URL, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await delay(2000);

      if (viewport.width === 375) {
        // No horizontal scroll
        const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
        const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
        const noHScroll = scrollWidth <= clientWidth + 2; // 2px tolerance
        record(viewport.name, 'No horizontal scroll at 375px', noHScroll, `scrollWidth=${scrollWidth}, clientWidth=${clientWidth}`);

        // Content stacks vertically (check that grid becomes single column)
        const heroGrid = await page.evaluate(() => {
          const hero = document.querySelector('#hero .grid');
          if (!hero) return 'no-grid';
          return window.getComputedStyle(hero).gridTemplateColumns;
        });
        const isStacked = heroGrid === 'no-grid' || heroGrid.split(' ').length <= 1;
        record(viewport.name, 'Hero stacks vertically at 375px', isStacked, `gridTemplateColumns: ${heroGrid}`);

        // Mobile hamburger menu button exists
        const hamburgerBtn = await page.locator('nav button').first().isVisible().catch(() => false);
        record(viewport.name, 'Mobile hamburger menu button exists', hamburgerBtn);

        // Desktop nav links hidden on mobile
        const desktopNavHidden = await page.evaluate(() => {
          const nav = document.querySelector('.md\\:flex.items-center.space-x-2');
          if (!nav) return true;
          return window.getComputedStyle(nav).display === 'none';
        });
        record(viewport.name, 'Desktop nav hidden at 375px', desktopNavHidden);

      } else if (viewport.width === 768) {
        // Tablet: content uses simplified/stacked layout
        const heroGrid = await page.evaluate(() => {
          const hero = document.querySelector('#hero .grid');
          if (!hero) return 'no-grid';
          return window.getComputedStyle(hero).gridTemplateColumns;
        });
        // At 768px, md:grid-cols-2 activates, so we expect 2 columns
        const hasTwoColumns = heroGrid !== 'no-grid' && heroGrid.split(' ').length >= 2;
        record(viewport.name, 'Hero uses 2-col grid at 768px', hasTwoColumns, `gridTemplateColumns: ${heroGrid}`);

        // Desktop nav visible at tablet
        const desktopNavVisible = await page.evaluate(() => {
          const nav = document.querySelector('.md\\:flex.items-center.space-x-2');
          if (!nav) return false;
          return window.getComputedStyle(nav).display !== 'none';
        });
        record(viewport.name, 'Desktop nav visible at 768px', desktopNavVisible);

      } else if (viewport.width === 1280) {
        // Desktop: full horizontal navigation
        const desktopNavVisible = await page.evaluate(() => {
          const nav = document.querySelector('.md\\:flex.items-center.space-x-2');
          if (!nav) return false;
          return window.getComputedStyle(nav).display !== 'none';
        });
        record(viewport.name, 'Desktop nav visible at 1280px', desktopNavVisible);

        // Hero side-by-side layout
        const heroGrid = await page.evaluate(() => {
          const hero = document.querySelector('#hero .grid');
          if (!hero) return 'no-grid';
          return window.getComputedStyle(hero).gridTemplateColumns;
        });
        const hasMultiColumns = heroGrid !== 'no-grid' && heroGrid.split(' ').length >= 2;
        record(viewport.name, 'Hero side-by-side at 1280px', hasMultiColumns, `gridTemplateColumns: ${heroGrid}`);

        // Login/register buttons visible in nav
        const loginBtnVisible = await page.getByRole('link', { name: 'Masuk' }).isVisible().catch(() => false);
        const registerBtnVisible = await page.getByRole('link', { name: 'Registrasi' }).isVisible().catch(() => false);
        record(viewport.name, 'Nav "Masuk" button visible', loginBtnVisible);
        record(viewport.name, 'Nav "Registrasi" button visible', registerBtnVisible);
      }
    } catch (err) {
      record(viewport.name, 'Layout tests', false, err.message);
    }

    // ============================================================
    // TEST 4: Alur Kerja section - 6 numbered steps
    // ============================================================
    console.log('\n--- Alur Kerja Section ---');

    try {
      await page.goto(BASE_URL, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await delay(2000);

      const alurSection = await page.locator('#alur').isVisible().catch(() => false);
      record(viewport.name, 'Alur Kerja section exists', alurSection);

      // Count numbered steps (1. through 6.)
      const stepCount = await page.locator('#alur .text-3xl.font-bold').count().catch(() => 0);
      record(viewport.name, 'Alur Kerja has 6 numbered steps', stepCount === 6, `Found ${stepCount} steps`);

      // Verify step headings
      const stepTexts = await page.locator('#alur .text-xl.font-bold').allInnerTexts().catch(() => []);
      const hasPortal = stepTexts.some(t => t.includes('Portal'));
      const hasAkun = stepTexts.some(t => t.includes('Membuat Akun'));
      const hasIsi = stepTexts.some(t => t.includes('Mengisi'));
      const hasNilai = stepTexts.some(t => t.includes('Nilai Kuisioner'));
      const hasVerif = stepTexts.some(t => t.includes('Nilai Verifikasi'));
      const hasSelesai = stepTexts.some(t => t.includes('Selesai'));
      record(viewport.name, 'Step 1: Portal E-Monev', hasPortal);
      record(viewport.name, 'Step 2: Membuat Akun', hasAkun);
      record(viewport.name, 'Step 3: Mengisi Kuisioner', hasIsi);
      record(viewport.name, 'Step 4: Nilai Kuisioner', hasNilai);
      record(viewport.name, 'Step 5: Nilai Verifikasi', hasVerif);
      record(viewport.name, 'Step 6: Proses Selesai', hasSelesai);
    } catch (err) {
      record(viewport.name, 'Alur Kerja tests', false, err.message);
    }

    // ============================================================
    // TEST 5: Kontak section
    // ============================================================
    console.log('\n--- Kontak Section ---');

    try {
      await page.goto(BASE_URL, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await delay(2000);

      const kontakSection = await page.locator('#kontak').isVisible().catch(() => false);
      record(viewport.name, 'Kontak section exists', kontakSection);

      const emailLink = await page.locator('a[href="mailto:dinkominfobnakab@gmail.com"]').isVisible().catch(() => false);
      record(viewport.name, 'Kontak: email link present', emailLink);

      const whatsappText = await page.getByText('(+62) 812 1503 4540').isVisible().catch(() => false);
      record(viewport.name, 'Kontak: WhatsApp number present', whatsappText);
    } catch (err) {
      record(viewport.name, 'Kontak tests', false, err.message);
    }

    // ============================================================
    // TEST 6: Statistik section with counters
    // ============================================================
    console.log('\n--- Statistik Section ---');

    try {
      await page.goto(BASE_URL, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await delay(2000);

      const statistikSection = await page.locator('#statistik').isVisible().catch(() => false);
      record(viewport.name, 'Statistik section exists', statistikSection);

      const counterCount = await page.locator('.counter').count().catch(() => 0);
      record(viewport.name, 'Statistik has counter elements', counterCount > 0, `Found ${counterCount} counters`);

      // Check that counters have data-target attributes
      const hasDataTarget = await page.evaluate(() => {
        const counters = document.querySelectorAll('.counter');
        return Array.from(counters).some(c => c.hasAttribute('data-target'));
      }).catch(() => false);
      record(viewport.name, 'Counters have data-target attributes', hasDataTarget);
    } catch (err) {
      record(viewport.name, 'Statistik tests', false, err.message);
    }

    // ============================================================
    // TEST 7: 404 page
    // ============================================================
    console.log('\n--- Edge Cases ---');

    try {
      const response = await page.goto(`${BASE_URL}/this-page-does-not-exist-xyz123`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await delay(2000);

      const is404 = response.status() === 404;
      record(viewport.name, '404 page returns 404 status', is404, `Status: ${response.status()}`);
    } catch (err) {
      // Some Laravel setups redirect 404s; check if we got an error page
      const bodyText = await page.innerText('body').catch(() => '');
      const hasError = bodyText.includes('404') || bodyText.includes('Not Found') || bodyText.includes('Page Not Found');
      record(viewport.name, '404 page shows error content', hasError, err.message);
    }

    // ============================================================
    // TEST 8: Internal links are valid
    // ============================================================
    console.log('\n--- Link Validation ---');

    try {
      await page.goto(BASE_URL, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await delay(2000);

      // Collect all internal hrefs from the landing page
      const links = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('a[href]'))
          .map(a => ({ text: a.innerText.trim(), href: a.getAttribute('href') }))
          .filter(a => a.href && (a.href.startsWith('/') || a.href.startsWith('http')));
      });

      let validLinks = 0;
      let brokenLinks = 0;

      for (const link of links) {
        // Skip external links and anchor-only links
        if (link.href.startsWith('http') && !link.href.includes('127.0.0.1')) continue;
        if (link.href === '#' || link.href.startsWith('#')) continue;

        try {
          const resp = await page.request.get(link.href.startsWith('http') ? link.href : `${BASE_URL}${link.href}`, { timeout: 10000 });
          if (resp.status() < 400) {
            validLinks++;
          } else {
            brokenLinks++;
            record(viewport.name, `Link valid: ${link.text}`, false, `${link.href} -> ${resp.status()}`);
          }
        } catch (e) {
          brokenLinks++;
          record(viewport.name, `Link valid: ${link.text}`, false, `${link.href} -> error`);
        }
      }

      record(viewport.name, `Internal links valid (${validLinks}/${validLinks + brokenLinks})`, brokenLinks === 0, `${brokenLinks} broken`);
    } catch (err) {
      record(viewport.name, 'Link validation', false, err.message);
    }

    // Final screenshot for this viewport
    await page.screenshot({ path: path.join(SCREENSHOT_DIR, `${viewport.name.toLowerCase()}-fullpage.png`), fullPage: true });

  } catch (err) {
    console.error(`Fatal error testing ${viewport.name}:`, err.message);
  } finally {
    await browser.close();
  }
}

async function main() {
  console.log('E-Monev KIP - Mobile Responsiveness Test Suite');
  console.log(`Base URL: ${BASE_URL}`);
  console.log(`Started: ${new Date().toISOString()}`);

  const viewports = [
    { name: 'Mobile', width: 375, height: 667 },
    { name: 'Tablet', width: 768, height: 1024 },
    { name: 'Desktop', width: 1280, height: 720 },
  ];

  for (const vp of viewports) {
    await testViewport(vp);
  }

  // Summary
  console.log('\n' + '='.repeat(60));
  console.log('SUMMARY');
  console.log('='.repeat(60));

  const passed = results.filter(r => r.passed).length;
  const total = results.length;

  // Per-viewport breakdown
  for (const vp of viewports) {
    const vpResults = results.filter(r => r.viewport === vp.name);
    const vpPassed = vpResults.filter(r => r.passed).length;
    console.log(`  ${vp.name} (${vp.width}x${vp.height}): ${vpPassed}/${vpResults.length} passed`);
  }

  console.log(`\n  TOTAL: ${passed}/${total} tests passed`);

  if (passed === total) {
    console.log('\n  All tests passed!');
  } else {
    console.log('\n  Failed tests:');
    results.filter(r => !r.passed).forEach(r => {
      console.log(`    [${r.viewport}] ${r.test}${r.detail ? ' - ' + r.detail : ''}`);
    });
  }

  console.log(`\nScreenshots saved to: ${SCREENSHOT_DIR}`);
  console.log(`Finished: ${new Date().toISOString()}`);

  // Exit with appropriate code
  process.exit(passed === total ? 0 : 1);
}

main().catch(err => {
  console.error('Unhandled error:', err);
  process.exit(2);
});
