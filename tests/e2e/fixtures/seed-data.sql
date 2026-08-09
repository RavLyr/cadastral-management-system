--
-- PostgreSQL database dump
--

-- Dumped from database version 16.4 (Debian 16.4-1.pgdg110+2)
-- Dumped by pg_dump version 16.4 (Debian 16.4-1.pgdg110+2)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: map_blok; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.map_blok (id, nama_blok, file_pdf, skala, deskripsi, created_at, updated_at) VALUES (1, 'BLOK CENDANA 01', 'peta-blok/seed-peta-blok.pdf', '1:1000', 'Blok fixture E2E', '2026-08-09 08:18:16', '2026-08-09 08:18:16');
INSERT INTO public.map_blok (id, nama_blok, file_pdf, skala, deskripsi, created_at, updated_at) VALUES (2, 'BLOK MELATI 02', 'peta-blok/seed-peta-blok.pdf', '1:2000', 'Blok fixture E2E', '2026-08-09 08:18:16', '2026-08-09 08:18:16');


--
-- Data for Name: dhr_desa_temurejo; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.dhr_desa_temurejo (id, nop, objek_pajak_jalan_dusun_op, objek_pajak_rt, objek_pajak_rw, objek_pajak_desa, subjek_pajak_nama_wajib_pajak, subjek_pajak_jalan_dusun, subjek_pajak_rt, subjek_pajak_rw, subjek_pajak_desa_kel, subjek_pajak_kabupaten_kota, bumi, bng, jns_bumi, usulan_pembetulan, blok, no_urut, created_at, updated_at, map_blok_id, nop_raw) VALUES (1, '3323', 'DUSUN DUA', '002', '002', 'TEMUREJO', 'SRI RAHAYU E2E', 'RT 2', '002', '002', 'TEMUREJO', 'GROBOGAN', '12000', '0', 'TEGAL', NULL, 'BLOK CENDANA 01', '2', '2026-08-09 08:19:26', '2026-08-09 08:19:26', 1, '33.24.09.008.002-00003.0');


--
-- Data for Name: gis_bidang_tanah; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.gis_bidang_tanah (id, nop, properties, created_at, updated_at, geom, nop_raw) VALUES (1, '3321', '{"D_LUAS": 2500}', '2026-08-09 08:18:51', '2026-08-09 08:18:51', '0106000020E61000000100000001030000000100000005000000302AA913D0AC5B409F3C2CD49A661CC046B6F3FDD4AC5B409F3C2CD49A661CC046B6F3FDD4AC5B4010E9B7AF03671CC0302AA913D0AC5B4010E9B7AF03671CC0302AA913D0AC5B409F3C2CD49A661CC0', NULL);
INSERT INTO public.gis_bidang_tanah (id, nop, properties, created_at, updated_at, geom, nop_raw) VALUES (2, '3323', '{"D_LUAS": 9000}', '2026-08-09 08:18:51', '2026-08-09 08:18:51', '0106000020E610000001000000010300000001000000050000009BE61DA7E8AC5B404703780B24681CC0B0726891EDAC5B404703780B24681CC0B0726891EDAC5B40B8AF03E78C681CC09BE61DA7E8AC5B40B8AF03E78C681CC09BE61DA7E8AC5B404703780B24681CC0', NULL);


--
-- Data for Name: tanah; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.tanah (id, no_urut, nama_wajib_ipeda, tempat_tinggal, nomor_persil, kelas_desa, luas_ha, luas_da, ipeda_r, ipeda_s, sebab_perubahan, tgl_perubahan, jenis_tanah, blok_id, created_by, created_at, updated_at, nop, nop_raw, parent_id, luas_awal_ha, luas_awal_da, luas_sisa_ha, luas_sisa_da) VALUES (1, '1', 'E2E BUAH MERAH', 'Dusun Satu, Temurejo', 'P001', NULL, 2.5000, 1.0000, 100000.00, 2000.00, NULL, NULL, 'basah', 1, NULL, '2026-08-09 08:18:16', '2026-08-09 08:18:16', '3321', '33.24.09.008.002-00001.0', NULL, 2.5000, 1.0000, 2.5000, 1.0000);


--
-- Data for Name: tanah_histories; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Name: dhr_desa_temurejo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.dhr_desa_temurejo_id_seq', 2, true);


--
-- Name: gis_bidang_tanah_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.gis_bidang_tanah_id_seq', 2, true);


--
-- Name: map_blok_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.map_blok_id_seq', 2, true);


--
-- Name: tanah_histories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tanah_histories_id_seq', 1, false);


--
-- Name: tanah_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tanah_id_seq', 1, true);


--
-- PostgreSQL database dump complete
--

