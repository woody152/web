import os
import sys

import pytest

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from sina_fetcher import (  # noqa: E402
    SinaDataParser,
    _parse_sina_response,
    parse_fields,
    load_stock_data,
    fetch_sina_quotes,
    rtrim0,
)


def _net_available():
    try:
        fetch_sina_quotes(['sz162411'], use_cache=False, timeout=8)
        return True
    except Exception:
        return False


# Real Sina line samples (captured live), used for deterministic offline tests.
LINE_CN = ('var hq_str_sz162411="\u534e\u6cb9,0.891,0.891,0.913,0.915,0.889,'
           '0.912,0.913,450669050,406234599.722,1495300,0.912,1017200,0.911,'
           '2230574,0.910,960700,0.909,717800,0.908,605171,0.913,1059900,0.914,'
           '1285002,0.915,163602,0.916,139500,0.917,2026-06-08,15:00:00,00";')
LINE_US = ('var hq_str_gb_xop="XOP NAME,168.2300,1.35,2026-06-08 23:36:45,2.2400,'
           '168.1000,170.6350,167.8100,190.3600,118.9550,1679373,3195734,0,0.00,'
           '--,0.00,0.00,0.00,0.00,0,0,0.0000,0.00,0.00,,Jun 08 11:36AM EDT,'
           '165.9900,0,1,2026,283871768.0000,0.0000,0.0000,0.0000,0.0000,'
           '165.9900";')
LINE_FOREX = ('var hq_str_fx_susdcny="23:36:02,6.7825,6.7842,6.7878,101.00,'
              '6.7860,6.7900,6.7799,6.7842,USDCNY NAME,-0.0036,0.0101,X,0.0000,'
              '0.0000,,2026-06-08";')
LINE_FUTURE = ('var hq_str_hf_ES="7447.725,,7444.500,7444.750,7476.750,7355.500,'
               '23:36:48,7400.500,7368.000,0,11,18,2026-06-08,ES NAME,0";')
LINE_FUND = 'var hq_str_f_162411="FUND NAME,0.8782,0.8782,0.904,2026-06-05,37.2231";'


def _fields(line, sym):
    return _parse_sina_response(line)[sym]


# --- Deterministic parser tests (no network) --------------------------------
def test_parse_response_extracts_fields():
    parsed = _parse_sina_response(LINE_CN + '\n' + LINE_FUND)
    assert 'sz162411' in parsed
    assert parsed['sz162411'][0] == '\u534e\u6cb9'
    assert parsed['sz162411'][3] == '0.913'
    assert parsed['f_162411'][1] == '0.8782'


def test_parse_cn():
    d = SinaDataParser.parse_cn(_fields(LINE_CN, 'sz162411'))
    assert d['name'] == '\u534e\u6cb9'
    assert d['open'] == '0.891'
    assert d['prev_price'] == '0.891'
    assert d['price'] == '0.913'
    assert d['high'] == '0.915'
    assert d['low'] == '0.889'
    assert d['volume'] == '450669050'
    assert d['date'] == '2026-06-08'
    assert d['time'] == '15:00:00'
    assert d['bid_price'][0] == '0.912'
    assert d['bid_quantity'][0] == '1495300'
    assert d['ask_price'][0] == '0.913'
    assert d['ask_quantity'][0] == '605171'
    assert len(d['bid_price']) == 5 and len(d['ask_price']) == 5


def test_parse_us():
    d = SinaDataParser.parse_us(_fields(LINE_US, 'gb_xop'))
    assert d['name'] == 'XOP NAME'
    assert d['price'] == '168.2300'
    assert d['prev_price'] == '165.9900'
    assert d['open'] == '168.1000'
    assert d['high'] == '170.6350'
    assert d['low'] == '167.8100'
    assert d['volume'] == '1679373'
    assert d['date'] == '2026-06-08'
    assert d['time'] == '11:36:00'


def test_parse_forex():
    d = SinaDataParser.parse_forex(_fields(LINE_FOREX, 'fx_susdcny'), 'fx_susdcny')
    assert d['price'] == '6.7842'
    assert d['prev_price'] == '6.7878'
    assert d['name'] == 'USDCNY NAME'
    assert d['date'] == '2026-06-08'
    assert d['time'] == '23:36:02'


def test_parse_forex_jpy_multiplies_by_100():
    fields = _fields(LINE_FOREX, 'fx_susdcny')
    d = SinaDataParser.parse_forex(fields, 'fx_sjpycny')
    assert abs(float(d['price']) - 678.42) < 1e-6
    assert abs(float(d['prev_price']) - 678.78) < 1e-6


def test_parse_future_us():
    d = SinaDataParser.parse_future_us(_fields(LINE_FUTURE, 'hf_ES'))
    assert d['price'] == '7447.725'
    assert d['prev_price'] == '7400.500'
    assert d['date'] == '2026-06-08'
    assert d['time'] == '23:36:48'
    assert d['name'] == 'ES NAME'
    assert d['open'] == '7368.000'
    assert d['high'] == '7476.750'
    assert d['low'] == '7355.500'


def test_parse_fund():
    d = SinaDataParser.parse_fund(_fields(LINE_FUND, 'f_162411'))
    assert d['name'] == 'FUND NAME'
    assert d['price'] == '0.8782'      # net value
    assert d['prev_price'] == '0.904'
    assert d['date'] == '2026-06-05'


def test_dispatch_via_parse_fields():
    assert parse_fields('SZ162411', _fields(LINE_CN, 'sz162411'))['price'] == '0.913'
    assert parse_fields('XOP', _fields(LINE_US, 'gb_xop'))['price'] == '168.2300'
    assert parse_fields('fx_susdcny', _fields(LINE_FOREX, 'fx_susdcny'))['price'] == '6.7842'
    assert parse_fields('hf_ES', _fields(LINE_FUTURE, 'hf_ES'))['price'] == '7447.725'
    assert parse_fields('f_162411', _fields(LINE_FUND, 'f_162411'))['price'] == '0.8782'


def test_rtrim0():
    assert rtrim0('0.910') == '0.91'
    assert rtrim0('100') == '100'
    assert rtrim0('0.913') == '0.913'


# --- Live smoke tests (skipped without network) -----------------------------
network = pytest.mark.skipif(not _net_available(), reason='Sina network unavailable')


@network
def test_live_load_cn():
    d = load_stock_data('SZ162411', use_cache=False)
    assert d is not None
    for k in ('price', 'date', 'name'):
        assert k in d and d[k]


@network
def test_live_load_us():
    d = load_stock_data('XOP', use_cache=False)
    assert d is not None
    assert 'price' in d and float(d['price']) > 0


@network
def test_live_load_forex():
    d = load_stock_data('fx_susdcny', use_cache=False)
    assert d is not None
    assert float(d['price']) > 0


@network
def test_live_load_future():
    d = load_stock_data('hf_ES', use_cache=False)
    assert d is not None
    assert float(d['price']) > 0
