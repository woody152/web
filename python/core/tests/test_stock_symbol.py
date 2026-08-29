import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from stock_symbol import (  # noqa: E402
    StockSymbol,
    in_arrayQdii,
    in_arrayQdiiHk,
    in_arrayChinaIndex,
    in_arrayChinaFuture,
    build_china_fund_symbol,
    build_china_stock_symbol,
    build_yahoo_net_value_symbol,
    is_china_stock_digit,
    qdii_get_symbol_array,
    get_all_symbol_array,
)


# --- Validation cases from the migration spec -------------------------------
def test_spec_is_fund_a():
    assert StockSymbol('SZ162411').is_fund_a() == '162411'


def test_spec_is_lof_a():
    assert StockSymbol('SZ162411').is_lof_a() is True


def test_spec_is_etf_a():
    assert StockSymbol('SH513350').is_etf_a() is True


def test_spec_is_symbol_us():
    assert StockSymbol('XOP').is_symbol_us() is True


def test_spec_is_symbol_h_index():
    assert StockSymbol('^HSI').is_symbol_h() is True


def test_spec_is_sina_future_us():
    assert StockSymbol('hf_ES').is_sina_future_us() == 'ES'


def test_spec_get_sina_symbol_a():
    assert StockSymbol('SZ162411').get_sina_symbol() == 'sz162411'


def test_spec_get_sina_symbol_us():
    assert StockSymbol('XOP').get_sina_symbol() == 'gb_xop'


def test_spec_in_array_qdii():
    assert in_arrayQdii('SZ162411') is True


def test_spec_in_array_qdii_hk():
    assert in_arrayQdiiHk('SH513180') is True


# --- Additional coverage ----------------------------------------------------
def test_is_china_stock_digit():
    assert is_china_stock_digit('162411') == '162411'
    assert is_china_stock_digit('1624') is None
    assert is_china_stock_digit('16241X') is None


def test_is_symbol_a_variants():
    assert StockSymbol('SH513350').is_symbol_a() == '513350'
    assert StockSymbol('SZ162411').is_symbol_a() == '162411'
    assert StockSymbol('XOP').is_symbol_a() is None
    assert StockSymbol('^HSI').is_symbol_a() is None


def test_etf_vs_lof():
    # SH513350 is a Shanghai ETF range fund, not a LOF
    sym = StockSymbol('SH513350')
    assert sym.is_etf_a() is True
    assert sym.is_lof_a() is False
    # SZ162411 is a Shenzhen LOF range fund, not an ETF
    sym = StockSymbol('SZ162411')
    assert sym.is_lof_a() is True
    assert sym.is_etf_a() is False


def test_is_symbol_h_hongkong_digit():
    assert StockSymbol('09988').is_symbol_h() is True
    assert StockSymbol('09988').is_symbol_us() is False


def test_sina_future_cn_and_forex():
    assert StockSymbol('nf_AG0').is_sina_future_cn() == 'AG0'
    assert StockSymbol('nf_AG0').is_sina_future_us() is None
    assert StockSymbol('fx_susdcny').is_sina_forex() == 'USDCNY'
    assert StockSymbol('fx_susdcny').is_forex() is True
    assert StockSymbol('USCNY').is_east_money_forex() is True


def test_is_sina_fund_and_global_index():
    assert StockSymbol('f_240019').is_sina_fund() == '240019'
    assert StockSymbol('SZ162411').is_sina_fund() is None
    assert StockSymbol('znb_DAX').is_sina_global_index() == 'DAX'


def test_get_sina_symbol_more():
    assert StockSymbol('SH513350').get_sina_symbol() == 'sh513350'
    assert StockSymbol('^HSI').get_sina_symbol() == 'rt_hkHSI'
    assert StockSymbol('^DJI').get_sina_symbol() == 'gb_dji'
    assert StockSymbol('znb_DAX').get_sina_symbol() == 'znb_DAX'
    assert StockSymbol('09988').get_sina_symbol() == 'rt_hk09988'


def test_get_sina_fund_symbol():
    assert StockSymbol('SZ162411').get_sina_fund_symbol() == 'f_162411'
    assert StockSymbol('XOP').get_sina_fund_symbol() is None


def test_get_precision():
    assert StockSymbol('SZ162411').get_precision() == 3       # fund A
    assert StockSymbol('fx_susdcny').get_precision() == 4     # forex
    assert StockSymbol('XOP').get_precision() == 2            # us stock


def test_builders():
    assert build_china_fund_symbol('162411') == 'SZ162411'
    assert build_china_fund_symbol('513350') == 'SH513350'
    assert build_china_fund_symbol('600000') is None
    assert build_china_stock_symbol('600000') == 'SH600000'
    assert build_china_stock_symbol('000001') == 'SZ000001'
    assert build_china_stock_symbol('430047') == 'BJ430047'
    assert build_yahoo_net_value_symbol('XOP') == '^XOP-IV'
    assert build_yahoo_net_value_symbol('') is None


def test_array_membership():
    assert in_arrayChinaIndex('SH510300') is True
    assert in_arrayChinaFuture('SH518800') is True
    assert 'SZ162411' in qdii_get_symbol_array()
    # GetAllSymbolArray aggregates everything
    all_syms = get_all_symbol_array()
    assert 'SZ162411' in all_syms
    assert 'SH513180' in all_syms
