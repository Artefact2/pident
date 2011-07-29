--
-- PostgreSQL database dump
--

-- Dumped from database version 9.0.4
-- Dumped by pg_dump version 9.0.4
-- Started on 2011-07-29 15:40:00 CEST

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- TOC entry 331 (class 2612 OID 11574)
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: -; Owner: -
--

CREATE OR REPLACE PROCEDURAL LANGUAGE plpgsql;


SET search_path = public, pg_catalog;

SET default_with_oids = false;

--
-- TOC entry 1523 (class 1259 OID 20086)
-- Dependencies: 6
-- Name: blocks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE blocks (
    hash bit(256) NOT NULL,
    "time" bigint NOT NULL,
    found_by character varying(127),
    previous_hash bit(256),
    number bigint NOT NULL,
    coinbase bytea NOT NULL,
    size integer NOT NULL
);


--
-- TOC entry 1529 (class 1259 OID 20678)
-- Dependencies: 6
-- Name: blocks_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE blocks_transactions (
    block bit(256) NOT NULL,
    transaction_id bit(256) NOT NULL
);


--
-- TOC entry 1525 (class 1259 OID 20092)
-- Dependencies: 6
-- Name: tx_in; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE tx_in (
    transaction_id bit(256) NOT NULL,
    previous_n integer NOT NULL,
    previous_out bit(256) NOT NULL,
    n integer NOT NULL
);


--
-- TOC entry 1526 (class 1259 OID 20095)
-- Dependencies: 1818 6
-- Name: tx_out; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE tx_out (
    transaction_id bit(256) NOT NULL,
    n integer NOT NULL,
    amount bigint NOT NULL,
    address bit(160),
    is_payout boolean DEFAULT false NOT NULL,
    type character varying(127) NOT NULL
);


--
-- TOC entry 1530 (class 1259 OID 21880)
-- Dependencies: 1621 6
-- Name: address_from; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW address_from AS
    SELECT a.address, a.transaction_id, b.address AS address_from, a.amount, blocks.hash AS block, blocks.number FROM ((((tx_out a JOIN blocks_transactions ON ((blocks_transactions.transaction_id = a.transaction_id))) JOIN blocks ON ((blocks.hash = blocks_transactions.block))) LEFT JOIN tx_in ON ((tx_in.transaction_id = a.transaction_id))) LEFT JOIN tx_out b ON (((b.transaction_id = tx_in.previous_out) AND (b.n = tx_in.previous_n))));


--
-- TOC entry 1531 (class 1259 OID 21885)
-- Dependencies: 1622 6
-- Name: address_to; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW address_to AS
    SELECT a.address, b.transaction_id, b.address AS address_to, a.amount, blocks.hash AS block, a.transaction_id AS transaction_id_from, blocks.number FROM ((((tx_out a JOIN tx_in ON (((tx_in.previous_out = a.transaction_id) AND (tx_in.previous_n = a.n)))) JOIN blocks_transactions ON ((blocks_transactions.transaction_id = tx_in.transaction_id))) JOIN blocks ON ((blocks.hash = blocks_transactions.block))) JOIN tx_out b ON ((b.transaction_id = tx_in.transaction_id)));


--
-- TOC entry 1524 (class 1259 OID 20089)
-- Dependencies: 1817 6
-- Name: transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE transactions (
    transaction_id bit(256) NOT NULL,
    size integer NOT NULL,
    is_generation boolean DEFAULT false NOT NULL
);


--
-- TOC entry 1534 (class 1259 OID 32774)
-- Dependencies: 1625 6
-- Name: blocks_generated; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW blocks_generated AS
    SELECT sum(tx_out.amount) AS generated, blocks.number FROM (((tx_out JOIN transactions ON (((transactions.transaction_id = tx_out.transaction_id) AND (transactions.is_generation = true)))) JOIN blocks_transactions ON ((blocks_transactions.transaction_id = tx_out.transaction_id))) JOIN blocks ON ((blocks.hash = blocks_transactions.block))) GROUP BY blocks.number;


--
-- TOC entry 1527 (class 1259 OID 20121)
-- Dependencies: 6
-- Name: scores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE scores (
    address bit(160) NOT NULL,
    pool character varying(127) NOT NULL,
    score double precision NOT NULL
);


--
-- TOC entry 1532 (class 1259 OID 21905)
-- Dependencies: 1623 6
-- Name: scores_blocks; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW scores_blocks AS
    SELECT blocks_transactions.block, scores.pool, sum(scores.score) AS score_total FROM ((blocks_transactions JOIN tx_out ON ((tx_out.transaction_id = blocks_transactions.transaction_id))) JOIN scores ON ((scores.address = tx_out.address))) GROUP BY scores.pool, blocks_transactions.block;


--
-- TOC entry 1528 (class 1259 OID 20128)
-- Dependencies: 6
-- Name: scores_pool_averages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE scores_pool_averages (
    pool character varying(127) NOT NULL,
    score_average double precision NOT NULL,
    score_stddev double precision NOT NULL,
    block_size_average double precision NOT NULL,
    block_size_stddev double precision NOT NULL,
    transaction_count_average double precision NOT NULL,
    transaction_count_stddev double precision NOT NULL,
    generation_addresses_average double precision NOT NULL,
    generation_addresses_stddev double precision NOT NULL,
    coinbase_distance_average double precision NOT NULL,
    coinbase_distance_stddev double precision NOT NULL
);


--
-- TOC entry 1533 (class 1259 OID 32765)
-- Dependencies: 1624 6
-- Name: transaction_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW transaction_count AS
    SELECT blocks.hash, count(blocks_transactions.transaction_id) AS count FROM (blocks JOIN blocks_transactions ON ((blocks_transactions.block = blocks.hash))) GROUP BY blocks.hash;


--
-- TOC entry 1822 (class 2606 OID 20142)
-- Dependencies: 1523 1523
-- Name: blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY blocks
    ADD CONSTRAINT blocks_pkey PRIMARY KEY (hash);


--
-- TOC entry 1843 (class 2606 OID 20682)
-- Dependencies: 1529 1529 1529
-- Name: blocks_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY blocks_transactions
    ADD CONSTRAINT blocks_transactions_pkey PRIMARY KEY (block, transaction_id);


--
-- TOC entry 1838 (class 2606 OID 20144)
-- Dependencies: 1527 1527 1527
-- Name: scores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY scores
    ADD CONSTRAINT scores_pkey PRIMARY KEY (address, pool);


--
-- TOC entry 1840 (class 2606 OID 20146)
-- Dependencies: 1528 1528
-- Name: scores_pool_avrages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY scores_pool_averages
    ADD CONSTRAINT scores_pool_avrages_pkey PRIMARY KEY (pool);


--
-- TOC entry 1827 (class 2606 OID 20148)
-- Dependencies: 1524 1524
-- Name: transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (transaction_id);


--
-- TOC entry 1832 (class 2606 OID 20150)
-- Dependencies: 1525 1525 1525
-- Name: tx_in_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tx_in
    ADD CONSTRAINT tx_in_pkey PRIMARY KEY (transaction_id, n);


--
-- TOC entry 1836 (class 2606 OID 20152)
-- Dependencies: 1526 1526 1526
-- Name: tx_out_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tx_out
    ADD CONSTRAINT tx_out_pkey PRIMARY KEY (transaction_id, n);


--
-- TOC entry 1819 (class 1259 OID 20153)
-- Dependencies: 1523
-- Name: blocks_found_by_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX blocks_found_by_idx ON blocks USING btree (found_by);


--
-- TOC entry 1820 (class 1259 OID 20154)
-- Dependencies: 1523
-- Name: blocks_number_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX blocks_number_idx ON blocks USING btree (number);


--
-- TOC entry 1823 (class 1259 OID 20155)
-- Dependencies: 1523
-- Name: blocks_time_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX blocks_time_idx ON blocks USING btree ("time");


--
-- TOC entry 1841 (class 1259 OID 21947)
-- Dependencies: 1529
-- Name: blocks_transactions_block_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX blocks_transactions_block_idx ON blocks_transactions USING btree (block);


--
-- TOC entry 1844 (class 1259 OID 21948)
-- Dependencies: 1529
-- Name: blocks_transactions_transaction_id_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX blocks_transactions_transaction_id_idx ON blocks_transactions USING btree (transaction_id);


--
-- TOC entry 1824 (class 1259 OID 20156)
-- Dependencies: 1523
-- Name: fki_blocks_previous_hash_fkey; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fki_blocks_previous_hash_fkey ON blocks USING btree (previous_hash);


--
-- TOC entry 1829 (class 1259 OID 20157)
-- Dependencies: 1525 1525
-- Name: fki_tx_id_previous_out_fkey; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fki_tx_id_previous_out_fkey ON tx_in USING btree (previous_out, n);


--
-- TOC entry 1830 (class 1259 OID 20158)
-- Dependencies: 1525 1525
-- Name: fki_tx_in_previous_out_fkey; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fki_tx_in_previous_out_fkey ON tx_in USING btree (previous_out, previous_n);


--
-- TOC entry 1825 (class 1259 OID 32769)
-- Dependencies: 1524
-- Name: transactions_is_generation_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX transactions_is_generation_idx ON transactions USING btree (is_generation);


--
-- TOC entry 1828 (class 1259 OID 32779)
-- Dependencies: 1524
-- Name: transactions_size_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX transactions_size_idx ON transactions USING btree (size);


--
-- TOC entry 1833 (class 1259 OID 20160)
-- Dependencies: 1526
-- Name: tx_out_address_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tx_out_address_idx ON tx_out USING btree (address);


--
-- TOC entry 1834 (class 1259 OID 20161)
-- Dependencies: 1526
-- Name: tx_out_is_payout_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tx_out_is_payout_idx ON tx_out USING btree (is_payout);


--
-- TOC entry 1845 (class 2606 OID 20162)
-- Dependencies: 1523 1523 1821
-- Name: blocks_previous_hash_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY blocks
    ADD CONSTRAINT blocks_previous_hash_fkey FOREIGN KEY (previous_hash) REFERENCES blocks(hash);


--
-- TOC entry 1849 (class 2606 OID 20683)
-- Dependencies: 1529 1821 1523
-- Name: blocks_transactions_block_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY blocks_transactions
    ADD CONSTRAINT blocks_transactions_block_fkey FOREIGN KEY (block) REFERENCES blocks(hash);


--
-- TOC entry 1850 (class 2606 OID 20688)
-- Dependencies: 1826 1524 1529
-- Name: blocks_transactions_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY blocks_transactions
    ADD CONSTRAINT blocks_transactions_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id);


--
-- TOC entry 1846 (class 2606 OID 20172)
-- Dependencies: 1826 1525 1524
-- Name: tx_id_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tx_in
    ADD CONSTRAINT tx_id_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id);


--
-- TOC entry 1847 (class 2606 OID 20177)
-- Dependencies: 1526 1835 1525 1525 1526
-- Name: tx_in_previous_out_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tx_in
    ADD CONSTRAINT tx_in_previous_out_fkey FOREIGN KEY (previous_out, previous_n) REFERENCES tx_out(transaction_id, n);


--
-- TOC entry 1848 (class 2606 OID 20182)
-- Dependencies: 1526 1826 1524
-- Name: tx_out_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tx_out
    ADD CONSTRAINT tx_out_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id);


-- Completed on 2011-07-29 15:40:01 CEST

--
-- PostgreSQL database dump complete
--

