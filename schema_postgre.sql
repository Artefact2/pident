--
-- PostgreSQL database dump
--

-- Dumped from database version 9.0.4
-- Dumped by pg_dump version 9.0.4
-- Started on 2011-07-11 14:08:33 CEST

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- TOC entry 322 (class 2612 OID 11574)
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: -; Owner: postgres
--

CREATE OR REPLACE PROCEDURAL LANGUAGE plpgsql;


ALTER PROCEDURAL LANGUAGE plpgsql OWNER TO postgres;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 1514 (class 1259 OID 17964)
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
-- TOC entry 1515 (class 1259 OID 17969)
-- Dependencies: 5
-- Name: transactions; Type: TABLE; Schema: public; Owner: pident; Tablespace: 
--

CREATE TABLE transactions (
    transaction_id bit(256) NOT NULL,
    block bit(256) NOT NULL
);


ALTER TABLE public.transactions OWNER TO pident;

--
-- TOC entry 1517 (class 1259 OID 18331)
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
-- TOC entry 1518 (class 1259 OID 18353)
-- Dependencies: 1804 5
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
-- TOC entry 1520 (class 1259 OID 19622)
-- Dependencies: 1610 5
-- Name: address_from; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW address_from AS
    SELECT a.address, a.transaction_id, b.address AS address_from, a.amount, blocks.hash AS block, blocks.number FROM ((((tx_out a LEFT JOIN transactions ON ((transactions.transaction_id = a.transaction_id))) LEFT JOIN blocks ON ((blocks.hash = transactions.block))) LEFT JOIN tx_in ON ((tx_in.transaction_id = transactions.transaction_id))) LEFT JOIN tx_out b ON (((b.transaction_id = tx_in.previous_out) AND (b.n = tx_in.previous_n))));


ALTER TABLE public.address_from OWNER TO pident;

--
-- TOC entry 1519 (class 1259 OID 19612)
-- Dependencies: 1609 5
-- Name: address_to; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW address_to AS
    SELECT a.address, b.transaction_id, b.address AS address_to, a.amount, blocks.hash AS block, a.transaction_id AS transaction_id_from, blocks.number FROM ((((tx_out a JOIN tx_in ON (((tx_in.previous_out = a.transaction_id) AND (tx_in.previous_n = a.n)))) JOIN transactions ON ((transactions.transaction_id = tx_in.transaction_id))) JOIN blocks ON ((blocks.hash = transactions.block))) JOIN tx_out b ON ((b.transaction_id = transactions.transaction_id)));


ALTER TABLE public.address_to OWNER TO pident;

--
-- TOC entry 1516 (class 1259 OID 18151)
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
-- TOC entry 1522 (class 1259 OID 19631)
-- Dependencies: 1612 5
-- Name: tx_total_in; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW tx_total_in AS
    SELECT transactions.transaction_id, sum(tx_out.amount) AS total_in FROM ((transactions LEFT JOIN tx_in ON ((transactions.transaction_id = tx_in.transaction_id))) LEFT JOIN tx_out ON (((tx_in.previous_out = tx_out.transaction_id) AND (tx_in.previous_n = tx_out.n)))) GROUP BY transactions.transaction_id;


ALTER TABLE public.tx_total_in OWNER TO pident;

--
-- TOC entry 1521 (class 1259 OID 19627)
-- Dependencies: 1611 5
-- Name: tx_total_out; Type: VIEW; Schema: public; Owner: pident
--

CREATE VIEW tx_total_out AS
    SELECT transactions.transaction_id, sum(tx_out.amount) AS total_out FROM (transactions LEFT JOIN tx_out ON ((transactions.transaction_id = tx_out.transaction_id))) GROUP BY transactions.transaction_id;


ALTER TABLE public.tx_total_out OWNER TO pident;

--
-- TOC entry 1808 (class 2606 OID 17968)
-- Dependencies: 1514 1514
-- Name: blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY blocks
    ADD CONSTRAINT blocks_pkey PRIMARY KEY (hash);


--
-- TOC entry 1814 (class 2606 OID 18347)
-- Dependencies: 1515 1515
-- Name: transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (transaction_id);


--
-- TOC entry 1818 (class 2606 OID 18582)
-- Dependencies: 1517 1517 1517
-- Name: tx_in_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY tx_in
    ADD CONSTRAINT tx_in_pkey PRIMARY KEY (transaction_id, n);


--
-- TOC entry 1821 (class 2606 OID 18358)
-- Dependencies: 1518 1518 1518
-- Name: tx_out_pkey; Type: CONSTRAINT; Schema: public; Owner: pident; Tablespace: 
--

ALTER TABLE ONLY tx_out
    ADD CONSTRAINT tx_out_pkey PRIMARY KEY (transaction_id, n);


--
-- TOC entry 1805 (class 1259 OID 19650)
-- Dependencies: 1514
-- Name: blocks_found_by_idx; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX blocks_found_by_idx ON blocks USING btree (found_by);


--
-- TOC entry 1806 (class 1259 OID 19649)
-- Dependencies: 1514
-- Name: blocks_number_idx; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX blocks_number_idx ON blocks USING btree (number);


--
-- TOC entry 1809 (class 1259 OID 19600)
-- Dependencies: 1514
-- Name: blocks_time_idx; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX blocks_time_idx ON blocks USING btree ("time");


--
-- TOC entry 1810 (class 1259 OID 19577)
-- Dependencies: 1514
-- Name: fki_blocks_previous_hash_fkey; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX fki_blocks_previous_hash_fkey ON blocks USING btree (previous_hash);


--
-- TOC entry 1815 (class 1259 OID 18959)
-- Dependencies: 1517 1517
-- Name: fki_tx_id_previous_out_fkey; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX fki_tx_id_previous_out_fkey ON tx_in USING btree (previous_out, n);


--
-- TOC entry 1816 (class 1259 OID 18965)
-- Dependencies: 1517 1517
-- Name: fki_tx_in_previous_out_fkey; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX fki_tx_in_previous_out_fkey ON tx_in USING btree (previous_out, previous_n);


--
-- TOC entry 1811 (class 1259 OID 18343)
-- Dependencies: 1515
-- Name: transactions_idx_block; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX transactions_idx_block ON transactions USING btree (block);


--
-- TOC entry 1812 (class 1259 OID 17981)
-- Dependencies: 1515
-- Name: transactions_idx_txid; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX transactions_idx_txid ON transactions USING btree (transaction_id);


--
-- TOC entry 1819 (class 1259 OID 19606)
-- Dependencies: 1518
-- Name: tx_out_address_idx; Type: INDEX; Schema: public; Owner: pident; Tablespace: 
--

CREATE INDEX tx_out_address_idx ON tx_out USING btree (address);


--
-- TOC entry 1822 (class 2606 OID 19572)
-- Dependencies: 1514 1514 1807
-- Name: blocks_previous_hash_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY blocks
    ADD CONSTRAINT blocks_previous_hash_fkey FOREIGN KEY (previous_hash) REFERENCES blocks(hash);


--
-- TOC entry 1823 (class 2606 OID 17976)
-- Dependencies: 1514 1515 1807
-- Name: transactions_fkey_block; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY transactions
    ADD CONSTRAINT transactions_fkey_block FOREIGN KEY (block) REFERENCES blocks(hash);


--
-- TOC entry 1824 (class 2606 OID 18348)
-- Dependencies: 1517 1515 1813
-- Name: tx_id_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY tx_in
    ADD CONSTRAINT tx_id_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id);


--
-- TOC entry 1825 (class 2606 OID 18960)
-- Dependencies: 1820 1517 1517 1518 1518
-- Name: tx_in_previous_out_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY tx_in
    ADD CONSTRAINT tx_in_previous_out_fkey FOREIGN KEY (previous_out, previous_n) REFERENCES tx_out(transaction_id, n);


--
-- TOC entry 1826 (class 2606 OID 18359)
-- Dependencies: 1518 1515 1813
-- Name: tx_out_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pident
--

ALTER TABLE ONLY tx_out
    ADD CONSTRAINT tx_out_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id);


--
-- TOC entry 1831 (class 0 OID 0)
-- Dependencies: 5
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2011-07-11 14:08:34 CEST

--
-- PostgreSQL database dump complete
--

