<style>
    .report-print-header {
        display: none;
    }

    .report-print-summary {
        display: none;
    }

    @media print {
        @page {
            size: landscape;
            margin: 12mm;
        }

        body {
            background: #fff !important;
            color: #000 !important;
            font-size: 10pt !important;
        }

        body > div.min-h-screen {
            display: block !important;
            min-height: 0 !important;
        }

        body > div.min-h-screen > aside,
        body > div.min-h-screen > header,
        body > div.min-h-screen > div > header {
            display: none !important;
        }

        body > div.min-h-screen > div {
            padding: 0 !important;
        }

        main {
            min-height: 0 !important;
        }

        .report-print-outer,
        .report-print-page {
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            color: #000 !important;
        }

        .report-print-outer > * {
            margin: 0 !important;
        }

        .report-print-page > * {
            margin-top: 0 !important;
            margin-bottom: 12pt !important;
        }

        .report-print-page .report-screen-only,
        .report-print-page .report-navigation,
        .report-print-page form,
        .report-print-page nav[aria-label$="pages"],
        .report-print-page .report-print-hide {
            display: none !important;
        }

        .report-print-header {
            display: block !important;
            margin-bottom: 16pt !important;
            border-bottom: 1px solid #000;
            padding-bottom: 8pt;
        }

        .report-print-summary {
            display: block !important;
        }

        .report-print-page h1,
        .report-print-page h2,
        .report-print-page h3,
        .report-print-page p,
        .report-print-page dt,
        .report-print-page dd,
        .report-print-page td,
        .report-print-page th,
        .report-print-page a,
        .report-print-page span {
            color: #000 !important;
        }

        .report-print-page a {
            text-decoration: none !important;
        }

        .report-print-page article,
        .report-print-page .report-table-wrap {
            break-inside: avoid;
            background: #fff !important;
            border-color: #999 !important;
            box-shadow: none !important;
        }

        .report-print-page .report-table-wrap {
            display: block !important;
            max-width: none !important;
            overflow: visible !important;
        }

        .report-print-page .report-mobile-cards {
            display: none !important;
        }

        .report-print-page table.report-print-table,
        .report-print-page table.report-print-table.hidden {
            display: table !important;
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 9pt !important;
        }

        .report-print-page table.report-print-table thead {
            display: table-header-group !important;
            background: #eee !important;
        }

        .report-print-page table.report-print-table tr {
            break-inside: avoid;
        }

        .report-print-page table.report-print-table th,
        .report-print-page table.report-print-table td {
            border: 1px solid #999 !important;
            padding: 5pt !important;
            vertical-align: top;
        }

        .report-print-page .grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<div class="report-screen-only mb-2">
    <button
        class="inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto print:hidden"
        type="button"
        onclick="window.print()"
    >Download as PDF</button>
    <p class="mt-1 text-sm text-slate-600 print:hidden">Opens your browser’s print dialog. Choose “Save as PDF” as the destination.</p>
</div>

<header class="report-print-header">
    <p class="text-sm font-semibold uppercase tracking-wide"><?= e(SHOP_NAME) ?></p>
    <h1 class="mt-1 text-2xl font-bold"><?= e($report_print_title) ?></h1>
    <p class="mt-1 text-base"><?= e($report_print_range) ?></p>
</header>
