<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Auth::login');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->post('daftar', 'Auth::register');

// Katalog dan detail boleh dilihat sebelum login.
$routes->get('paket-dekorasi', 'Packages::index');
$routes->get('paket/(:num)', 'Packages::detail/$1');

// Semua halaman kerja pelanggan dan aksi keranjang hanya untuk pelanggan login.
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);
$routes->match(['get', 'post'], 'logout', 'Auth::logout');
$routes->post('keranjang/paket/(:num)', 'Customer::addPackage/$1', ['filter' => 'auth']);
$routes->post('pesan-sekarang/(:num)', 'Customer::directBook/$1', ['filter' => 'auth']);
$routes->post('keranjang/tambahan/(:num)', 'Customer::addExtra/$1', ['filter' => 'auth']);
$routes->post('keranjang/update/(:segment)', 'Customer::updateCart/$1', ['filter' => 'auth']);
$routes->post('keranjang/hapus/(:segment)', 'Customer::removeCart/$1', ['filter' => 'auth']);
$routes->get('tambahan-dekorasi', 'Customer::extras', ['filter' => 'auth']);
$routes->get('keranjang', 'Customer::cart', ['filter' => 'auth']);
$routes->get('pembayaran', 'Customer::payment', ['filter' => 'auth']);
$routes->post('pembayaran', 'Customer::checkout', ['filter' => 'auth']);
$routes->get('booking', 'Customer::booking', ['filter' => 'auth']);
$routes->post('booking', 'Customer::saveBooking', ['filter' => 'auth']);
$routes->get('akun', 'Customer::account', ['filter' => 'auth']);
$routes->post('akun', 'Customer::updateAccount', ['filter' => 'auth']);
$routes->post('akun/ganti-password', 'Customer::updatePassword', ['filter' => 'auth']);
$routes->get('riwayat-pesanan', 'Customer::history', ['filter' => 'auth']);
$routes->get('riwayat-pesanan/(:num)/pelunasan', 'Customer::settlement/$1', ['filter' => 'auth']);
$routes->post('riwayat-pesanan/(:num)/pelunasan', 'Customer::submitSettlement/$1', ['filter' => 'auth']);
$routes->get('invoice', 'Customer::invoice', ['filter' => 'auth']);
$routes->post('invoice/(:num)/bukti', 'Customer::uploadPaymentProof/$1', ['filter' => 'auth']);
$routes->get('api/kelurahan/(:num)', 'Customer::apiKelurahanByKecamatan/$1');
$routes->get('api/paket/(:num)/booked-dates', 'Customer::apiBookedDates/$1');

// Login admin tidak berada di dalam grup yang membutuhkan sesi admin.
$routes->get('admin/login', 'AdminAuth::login');
$routes->post('admin/login', 'AdminAuth::attempt');

$routes->group('admin', ['filter' => 'admin'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Admin::dashboard');
    $routes->match(['get', 'post'], 'logout', 'AdminAuth::logout');

    $routes->get('paket', 'Admin::packages');
    $routes->get('paket/tambah', 'Admin::newPackage');
    $routes->post('paket', 'Admin::createPackage');
    $routes->get('paket/(:num)/edit', 'Admin::editPackage/$1');
    $routes->post('paket/(:num)', 'Admin::updatePackage/$1');
    $routes->post('paket/(:num)/status', 'Admin::togglePackage/$1');

    $routes->get('tambahan', 'Admin::extras');
    $routes->get('tambahan/tambah', 'Admin::newExtra');
    $routes->post('tambahan', 'Admin::createExtra');
    $routes->get('tambahan/(:num)/edit', 'Admin::editExtra/$1');
    $routes->post('tambahan/(:num)', 'Admin::updateExtra/$1');
    $routes->post('tambahan/(:num)/status', 'Admin::toggleExtra/$1');

    $routes->get('pesanan', 'Admin::orders');
    $routes->get('pesanan/(:num)', 'Admin::orderDetail/$1');
    $routes->post('pesanan/(:num)/status', 'Admin::updateOrderStatus/$1');
    $routes->get('pesanan/(:num)/bukti', 'Admin::paymentProof/$1');
    $routes->post('pesanan/(:num)/verifikasi', 'Admin::verifyPayment/$1');
    $routes->post('pesanan/(:num)/tandai-selesai', 'Admin::tandaiSelesai/$1');
    $routes->get('pelunasan/(:num)/bukti', 'Admin::settlementPaymentProof/$1');
    $routes->post('pelunasan/(:num)/verifikasi', 'Admin::reviewSettlement/$1');

    $routes->get('pelanggan', 'Admin::customers');

    // Aturan Ongkos Kirim
    $routes->get('ongkos-kirim', 'Admin::shippingZones');
    $routes->get('ongkos-kirim/kecamatan/tambah', 'Admin::newKecamatan');
    $routes->post('ongkos-kirim/kecamatan', 'Admin::createKecamatan');
    $routes->get('ongkos-kirim/kecamatan/(:num)/edit', 'Admin::editKecamatan/$1');
    $routes->post('ongkos-kirim/kecamatan/(:num)', 'Admin::updateKecamatan/$1');
    $routes->post('ongkos-kirim/kecamatan/(:num)/hapus', 'Admin::deleteKecamatan/$1');
    $routes->get('ongkos-kirim/kelurahan/tambah', 'Admin::newKelurahan');
    $routes->post('ongkos-kirim/kelurahan', 'Admin::createKelurahan');
    $routes->get('ongkos-kirim/kelurahan/(:num)/edit', 'Admin::editKelurahan/$1');
    $routes->post('ongkos-kirim/kelurahan/(:num)', 'Admin::updateKelurahan/$1');
    $routes->post('ongkos-kirim/kelurahan/(:num)/hapus', 'Admin::deleteKelurahan/$1');

    // Laporan transaksi, pendapatan terverifikasi, HPP, biaya usaha, dan export PDF.
    $routes->get('laporan', 'Admin::financialReports');
    $routes->get('laporan/pdf', 'Admin::financialReportPdf');
    $routes->post('laporan/biaya', 'Admin::createFinancialExpense');
    $routes->post('laporan/biaya/(:num)/batalkan', 'Admin::voidFinancialExpense/$1');
});
