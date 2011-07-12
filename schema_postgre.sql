--
-- PostgreSQL database dump
--

-- Dumped from database version 9.0.4
-- Dumped by pg_dump version 9.0.4
-- Started on 2011-07-12 13:58:02 CEST

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- TOC entry 334 (class 2612 OID 11574)
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: -; Owner: postgres
--

CREATE OR REPLACE PROCEDURAL LANGUAGE plpgsql;


ALTER PROCEDURAL LANGUAGE plpgsql OWNER TO postgres;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 1526 (class 1259 OID 17964)
-- Dependencies: 5
-- Name: blocks; Type: TABLE; Schema: public; Owner: pident; Tablespace: 
--

CREATE TABLE blocks (
    hash bit(256) NOT NULL,
    "time" bigint NOT NULL,
    found_by character varying(127),
    previous_hash bit(256),
    number bigint NOT NULL
);


ALTER TABLE public.blocks OWNER TO pident;

--
-- TOC entry 1527 (class 1259 OID 17969)
-- Dependencies: 5
-- Name: transactions; Type: TABLE; Schema: public; Owner: pident; Tablespace: 
--

CREATE TABLE transactions (
    transaction_id bit(256) NOT NULL,
    block bit(256) NOT NULL
);


ALTER TABLE public.transactions OWNER TO pident;

--
-- TOC entry 1529 (class 1259 OID 18331)
-- Dependencies: 5
-- Name: tx_in; Type: TABLE; Schema: public; Owner: pident; Tablespace: 
--

CREATE TABLE tx_in (
    transaction_id bit(256) NOT NULL,
    previous_n integer NOT NULL,
    previous_out bit(256) NOT NULL,
    n integer NOT NULL
);


ALTER TABLE public.tx_in OWNER TO pident;

--
-- TOC entry 1530 (class 1259 OID 18353)
-- Dependencies: 1826 5
-- Name: tx_out; Type: TABLE; Schema: public; Owner: pident; Tablespace: 
--

CREATE TABLE tx_out (
    transaction_id bit(256) NOT NULL,
    n integer NOT NULL,
    amount bigint NOT NULL,
    address bit(160),
    is_payout boolean DEFAULT false NOT NULL
);


ALTER TABLE public.tx_out OWNER TO pident;

--
-- TOC entry 1532 (class 1259 OID 19622)
-- Dependencies: 1628 5
-- Name: address_from; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW address_from AS
    SELECT a.address, a.transaction_id, b.address AS address_from, a.amount, blocks.hash AS block, blocks.number FROM ((((tx_out a LEFT JOIN transactions ON ((transactions.transaction_id = a.transaction_id))) LEFT JOIN blocks ON ((blocks.hash = transactions.block))) LEFT JOIN tx_in ON ((tx_in.transaction_id = transactions.transaction_id))) LEFT JOIN tx_out b ON (((b.transaction_id = tx_in.previous_out) AND (b.n = tx_in.previous_n))));


ALTER TABLE public.address_from OWNER TO pident;

--
-- TOC entry 1531 (class 1259 OID 19612)
-- Dependencies: 1627 5
-- Name: address_to; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW address_to AS
    SELECT a.address, b.transaction_id, b.address AS address_to, a.amount, blocks.hash AS block, a.transaction_id AS transaction_id_from, blocks.number FROM ((((tx_out a JOIN tx_in ON (((tx_in.previous_out = a.transaction_id) AND (tx_in.previous_n = a.n)))) JOIN transactions ON ((transactions.transaction_id = tx_in.transaction_id))) JOIN blocks ON ((blocks.hash = transactions.block))) JOIN tx_out b ON ((b.transaction_id = transactions.transaction_id)));


ALTER TABLE public.address_to OWNER TO pident;

--
-- TOC entry 1539 (class 1259 OID 19865)
-- Dependencies: 1633 5
-- Name: blocks_num_inputs; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW blocks_num_inputs AS
    SELECT transactions.block, count(*) AS num_inputs FROM (transactions JOIN tx_in ON ((tx_in.transaction_id = transactions.transaction_id))) GROUP BY transactions.block;


ALTER TABLE public.blocks_num_inputs OWNER TO pident;

--
-- TOC entry 1538 (class 1259 OID 19861)
-- Dependencies: 1632 5
-- Name: blocks_num_previous_outs; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW blocks_num_previous_outs AS
    SELECT transactions.block, count(*) AS num_previous_outs FROM ((transactions JOIN tx_in ON ((tx_in.transaction_id = transactions.transaction_id))) JOIN tx_out ON (((tx_out.transaction_id = tx_in.previous_out) AND (tx_out.n = tx_in.previous_n)))) GROUP BY transactions.block;


ALTER TABLE public.blocks_num_previous_outs OWNER TO pident;

--
-- TOC entry 1540 (class 1259 OID 19875)
-- Dependencies: 1634 5
-- Name: blocks_num_transactions; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW blocks_num_transactions AS
    SELECT transactions.block, count(*) AS num_transactions FROM transactions GROUP BY transactions.block;


ALTER TABLE public.blocks_num_transactions OWNER TO pident;

--
-- TOC entry 1535 (class 1259 OID 19701)
-- Dependencies: 5
-- Name: scores; Type: TABLE; Schema: public; Owner: pident; Tablespace: 
--

CREATE TABLE scores (
    address bit(160) NOT NULL,
    pool character varying(127) NOT NULL,
    score double precision NOT NULL
);


ALTER TABLE public.scores OWNER TO pident;

--
-- TOC entry 1536 (class 1259 OID 19714)
-- Dependencies: 1631 5
-- Name: scores_blocks; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW scores_blocks AS
    SELECT transactions.block, scores.pool, sum(scores.score) AS score_total FROM ((transactions JOIN tx_out ON ((tx_out.transaction_id = transactions.transaction_id))) JOIN scores ON ((scores.address = tx_out.address))) GROUP BY scores.pool, transactions.block;


ALTER TABLE public.scores_blocks OWNER TO pident;

--
-- TOC entry 1537 (class 1259 OID 19718)
-- Dependencies: 5
-- Name: scores_pool_averages; Type: TABLE; Schema: public; Owner: pident; Tablespace: 
--

CREATE TABLE scores_pool_averages (
    pool character varying(127) NOT NULL,
    average_score double precision NOT NULL
);


ALTER TABLE public.scores_pool_averages OWNER TO pident;

--
-- TOC entry 1528 (class 1259 OID 18151)
-- Dependencies: 5
-- Name: transactions_internal_id_seq; Type: SEQUENCE; Schema: public; Owner: pident
--

CREATE SEQUENCE transactions_internal_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.transactions_internal_id_seq OWNER TO pident;

--
-- TOC entry 1534 (class 1259 OID 19631)
-- Dependencies: 1630 5
-- Name: tx_total_in; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW tx_total_in AS
    SELECT transactions.transaction_id, sum(tx_out.amount) AS total_in FROM ((transactions LEFT JOIN tx_in ON ((transactions.transaction_id = tx_in.transaction_id))) LEFT JOIN tx_out ON (((tx_in.previous_out = tx_out.transaction_id) AND (tx_in.previous_n = tx_out.n)))) GROUP BY transactions.transaction_id;


ALTER TABLE public.tx_total_in OWNER TO pident;

--
-- TOC entry 1533 (class 1259 OID 19627)
-- Dependencies: 1629 5
-- Name: tx_total_out; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW tx_total_out AS
    SELECT transactions.transaction_id, sum(tx_out.amount) AS total_out FROM (transactions LEFT JOIN tx_out ON ((transactions.transaction_id = tx_out.transaction_id))) GROUP BY transactions.transaction_id;


ALTER TABLE public.tx_total_out OWNER TO pident;

--
-- TOC entry 1830 (class 2606 OID 17968)
-- Dependencies: 1526 1526
-- Name: blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY blocks
    ADD CONSTRAINT blocks_pkey PRIMARY KEY (hash);


--
-- TOC entry 1845 (class 2606 OID 19705)
-- Dependencies: 1535 1535 1535
-- Name: scores_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY scores
    ADD CONSTRAINT scores_pkey PRIMARY KEY (address, pool);


--
-- TOC entry 1847 (class 2606 OID 19722)
-- Dependencies: 1537 1537
-- Name: scores_pool_avrages_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY scores_pool_averages
    ADD CONSTRAINT scores_pool_avrages_pkey PRIMARY KEY (pool);


--
-- TOC entry 1835 (class 2606 OID 18347)
-- Dependencies: 1527 1527
-- Name: transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (transaction_id);


--
-- TOC entry 1839 (class 2606 OID 18582)
-- Dependencies: 1529 1529 1529
-- Name: tx_in_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY tx_in
    ADD CONSTRAINT tx_in_pkey PRIMARY KEY (transaction_id, n);


--
-- TOC entry 1843 (class 2606 OID 18358)
-- Dependencies: 1530 1530 1530
-- Name: tx_out_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY tx_out
    ADD CONSTRAINT tx_out_pkey PRIMARY KEY (transaction_id, n);


--
-- TOC entry 1827 (class 1259 OID 19650)
-- Dependencies: 1526
-- Name: blocks_found_by_idx; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX blocks_found_by_idx ON blocks USING btree (found_by);


--
-- TOC entry 1828 (class 1259 OID 19649)
-- Dependencies: 1526
-- Name: blocks_number_idx; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX blocks_number_idx ON blocks USING btree (number);


--
-- TOC entry 1831 (class 1259 OID 19600)
-- Dependencies: 1526
-- Name: blocks_time_idx; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX blocks_time_idx ON blocks USING btree ("time");


--
-- TOC entry 1832 (class 1259 OID 19577)
-- Dependencies: 1526
-- Name: fki_blocks_previous_hash_fkey; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX fki_blocks_previous_hash_fkey ON blocks USING btree (previous_hash);


--
-- TOC entry 1836 (class 1259 OID 18959)
-- Dependencies: 1529 1529
-- Name: fki_tx_id_previous_out_fkey; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX fki_tx_id_previous_out_fkey ON tx_in USING btree (previous_out, n);


--
-- TOC entry 1837 (class 1259 OID 18965)
-- Dependencies: 1529 1529
-- Name: fki_tx_in_previous_out_fkey; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX fki_tx_in_previous_out_fkey ON tx_in USING btree (previous_out, previous_n);


--
-- TOC entry 1833 (class 1259 OID 18343)
-- Dependencies: 1527
-- Name: transactions_idx_block; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX transactions_idx_block ON transactions USING btree (block);


--
-- TOC entry 1840 (class 1259 OID 19606)
-- Dependencies: 1530
-- Name: tx_out_address_idx; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX tx_out_address_idx ON tx_out USING btree (address);


--
-- TOC entry 1841 (class 1259 OID 19694)
-- Dependencies: 1530
-- Name: tx_out_is_payout_idx; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX tx_out_is_payout_idx ON tx_out USING btree (is_payout);


--
-- TOC entry 1848 (class 2606 OID 19572)
-- Dependencies: 1829 1526 1526
-- Name: blocks_previous_hash_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY blocks
    ADD CONSTRAINT blocks_previous_hash_fkey FOREIGN KEY (previous_hash) REFERENCES blocks(hash);


--
-- TOC entry 1849 (class 2606 OID 17976)
-- Dependencies: 1526 1829 1527
-- Name: transactions_fkey_block; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY transactions
    ADD CONSTRAINT transactions_fkey_block FOREIGN KEY (block) REFERENCES blocks(hash);


--
-- TOC entry 1850 (class 2606 OID 18348)
-- Dependencies: 1529 1834 1527
-- Name: tx_id_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY tx_in
    ADD CONSTRAINT tx_id_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id);


--
-- TOC entry 1851 (class 2606 OID 18960)
-- Dependencies: 1842 1529 1529 1530 1530
-- Name: tx_in_previous_out_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY tx_in
    ADD CONSTRAINT tx_in_previous_out_fkey FOREIGN KEY (previous_out, previous_n) REFERENCES tx_out(transaction_id, n);


--
-- TOC entry 1852 (class 2606 OID 18359)
-- Dependencies: 1527 1530 1834
-- Name: tx_out_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY tx_out
    ADD CONSTRAINT tx_out_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id);


--
-- TOC entry 1857 (class 0 OID 0)
-- Dependencies: 5
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2011-07-12 13:58:02 CEST

--
-- PostgreSQL database dump complete
--

