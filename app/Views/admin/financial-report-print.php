<?php
$viewScope = get_defined_vars();
$report = isset($viewScope['report']) && is_array($viewScope['report']) ? $viewScope['report'] : [];
unset($viewScope);
$summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
$filters = is_array($report['filters'] ?? null) ? $report['filters'] : [];
$transactions = is_array($report['transactions'] ?? null) ? $report['transactions'] : [];
$expenseRows = is_array($report['expenseRows'] ?? null) ? $report['expenseRows'] : [];
$currency = static fn ($value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Laporan Keuangan Nauli Dekor</title>
<style>
*{box-sizing:border-box}body{margin:0;padding:28px;color:#29231f;font:12px Arial,sans-serif;background:#fff}.toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}.toolbar button{border:0;background:#29231f;color:#fff;padding:10px 15px;cursor:pointer}.brand{font:600 19px Georgia,serif;letter-spacing:2px}.muted{color:#776d63}.header{border-bottom:2px solid #c29a3c;padding-bottom:15px;margin-bottom:20px;display:flex;justify-content:space-between}.header h1{font:normal 26px Georgia,serif;margin:5px 0}.period{font-size:11px;text-align:right}.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:20px 0}.card{border:1px solid #e8dfd3;padding:13px}.card span{display:block;color:#756b61;font-size:10px}.card strong{display:block;font:normal 18px Georgia,serif;margin-top:8px;overflow-wrap:anywhere}.section{margin-top:24px}.section h2{font:normal 19px Georgia,serif;border-bottom:1px solid #ded5c9;padding-bottom:8px}table{width:100%;border-collapse:collapse;margin-top:10px}th{background:#29231f;color:#fff;text-align:left;font-size:9px;padding:9px}td{border-bottom:1px solid #e8e2da;padding:8px;font-size:10px;vertical-align:top}.foot{margin-top:28px;border-top:1px solid #d8cbb9;padding-top:12px;color:#766e65;font-size:9px}@media print{body{padding:0}.toolbar{display:none}.card,table{break-inside:avoid}@page{size:A4 landscape;margin:12mm}}
</style></head><body>
<div class="toolbar"><div class="brand">✦ NAULI DEKOR <span class="muted">| Laporan Keuangan</span></div><button type="button" onclick="window.print()">Simpan sebagai PDF / Cetak</button></div>
<header class="header"><div><div class="muted">LAPORAN TRANSAKSI DAN KEUANGAN</div><h1>Nauli Dekor</h1><div class="muted">Disiapkan oleh Admin: <?= esc($adminPhone ?? '-') ?></div></div><div class="period"><b>Periode</b><br><?= esc(date('d/m/Y', strtotime((string) ($filters['tanggal_awal_hasil'] ?? date('Y-m-01'))))) ?> – <?= esc(date('d/m/Y', strtotime((string) ($filters['tanggal_akhir_hasil'] ?? date('Y-m-d'))))) ?><br>Dicetak: <?= esc(date('d/m/Y H:i')) ?></div></header>
<div class="cards">
<div class="card"><span>Total transaksi unik</span><strong><?= (int) ($summary['transactionCount'] ?? 0) ?></strong></div>
<div class="card"><span>Pendapatan terverifikasi</span><strong><?= esc($currency($summary['revenue'] ?? 0)) ?></strong></div>
<div class="card"><span>Total HPP</span><strong><?= esc($currency($summary['hpp'] ?? 0)) ?></strong></div>
<div class="card"><span>Keuntungan kotor (kas)</span><strong><?= esc($currency($summary['grossProfit'] ?? 0)) ?></strong></div>
<div class="card"><span>Biaya aktual usaha (HPP + operasional)</span><strong><?= esc($currency($summary['totalExpenses'] ?? 0)) ?></strong></div>
<div class="card"><span>Hasil bersih berbasis kas</span><strong><?= esc($currency($summary['netCashResult'] ?? 0)) ?></strong></div>
</div>
<section class="section"><h2>Rincian Transaksi dan Pembayaran</h2><table><thead><tr><th>Tanggal</th><th>Kode Pesanan</th><th>Pemesan</th><th>Jenis</th><th>Metode</th><th>Nominal</th><th>Status</th></tr></thead><tbody>
<?php if ($transactions === []): ?><tr><td colspan="7">Tidak ada transaksi pada periode ini.</td></tr><?php endif; ?>
<?php foreach ($transactions as $row): ?><tr><td><?= ! empty($row['tanggal_transaksi']) ? esc(date('d/m/Y H:i', strtotime($row['tanggal_transaksi']))) : '-' ?></td><td><?= esc($row['kode_pesanan'] ?? '-') ?></td><td><?= esc($row['nama_pemesan'] ?? '-') ?></td><td><?= esc($row['jenis_transaksi'] ?? '-') ?></td><td><?= esc($row['metode'] ?? '-') ?></td><td><?= esc($currency($row['jumlah'] ?? 0)) ?></td><td><?= esc($row['status_transaksi'] ?? '-') ?></td></tr><?php endforeach; ?>
</tbody></table></section>
<section class="section"><h2>Rincian Harga Perolehan dan Biaya Operasional</h2><table><thead><tr><th>Tanggal</th><th>Jenis</th><th>Kategori</th><th>Kode Pesanan</th><th>Keterangan</th><th>Jumlah</th><th>Status</th></tr></thead><tbody>
<?php if ($expenseRows === []): ?><tr><td colspan="7">Belum ada catatan biaya pada periode ini.</td></tr><?php endif; ?>
<?php foreach ($expenseRows as $row): ?><tr><td><?= esc(date('d/m/Y', strtotime($row['tanggal_biaya']))) ?></td><td><?= esc($row['jenis_biaya']) ?></td><td><?= esc($row['kategori']) ?></td><td><?= esc($row['kode_pesanan'] ?? '-') ?></td><td><?= esc($row['deskripsi']) ?></td><td><?= esc($currency($row['jumlah'])) ?></td><td><?= esc($row['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></section>
<footer class="foot">Pendapatan hanya menghitung pembayaran yang sudah diverifikasi. HPP dan biaya operasional merupakan biaya yang dimasukkan Admin. Hasil bersih berbasis kas bukan laporan akuntansi akrual. Dokumen ini dibuat dari data Nauli Dekor.</footer>
<script>window.addEventListener('load',()=>{window.setTimeout(()=>window.print(),250)});</script>
</body></html>
