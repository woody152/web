"""Stock symbol classification layer.

Faithful migration of ``php/stock/stocksymbol.php``. No IO.

PHP ``false`` return values are mapped to Python ``None`` (for string-like
returns) or ``False`` (for boolean returns), following the migration plan.
"""

# ---------------------------------------------------------------------------
# Constants (correspond to PHP define())
# ---------------------------------------------------------------------------
SINA_FOREX_PREFIX = 'fx_s'
SINA_FUTURE_PREFIX = 'hf_'
SINA_CN_FUTURE_PREFIX = 'nf_'
SINA_FUND_PREFIX = 'f_'
SINA_INDEX_PREFIX = 'znb_'
SINA_HK_PREFIX = 'rt_hk'
SINA_US_PREFIX = 'gb_'

BJ_PREFIX = 'BJ'
SH_PREFIX = 'SH'
SZ_PREFIX = 'SZ'

YAHOO_INDEX_CHAR = '^'


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
def str_has_prefix(s, prefix):
    """Corresponds to PHP ``StrHasPrefix()``.

    Returns the remainder of ``s`` after ``prefix`` if it matches, else None.
    """
    if s is not None and s.startswith(prefix):
        return s[len(prefix):]
    return None


# ---------------------------------------------------------------------------
# Symbol set functions (correspond to PHP *GetSymbolArray() / in_array*())
# ---------------------------------------------------------------------------
def get_secondary_listing_array():
    return {
        'BABA': '09988',
        'BIDU': '09888',
        'BILI': '09626',
        'JD': '09618',
        'NTES': '09999',
        'TCOM': '09961',
    }


def china_index_get_ashr_array():
    return ['SH501043', 'SH510300', 'SH510310', 'SH510330', 'SZ159919',
            'SZ160706', 'SZ161005', 'SZ163407', 'SZ168401']


def in_arrayAshrSymbol(symbol):
    return symbol in china_index_get_ashr_array()


def china_index_get_sz100_array():
    return ['SZ161227', 'SZ161812']


def china_index_get_coal_array():
    return ['SZ161032', 'SZ168204']


def china_index_get_symbol_array():
    return (china_index_get_ashr_array()
            + china_index_get_sz100_array()
            + china_index_get_coal_array()
            + ['SH502000', 'SZ160225', 'SZ160632', 'SZ160639', 'SZ161725',
               'SZ161726', 'SZ162412', 'SZ163109', 'SZ163113', 'SZ167301'])


def in_arrayChinaIndex(symbol):
    return symbol in china_index_get_symbol_array()


def china_future_get_symbol_array():
    return ['SH518800', 'SH518880', 'SZ159934', 'SZ159937', 'SZ159985', 'SZ161226']


def in_arrayChinaFuture(symbol):
    return symbol in china_future_get_symbol_array()


def qdii_get_oil_etf_symbol_array():
    return ['SZ160416', 'SZ162719']


def in_arrayOilEtfQdii(symbol):
    return symbol in qdii_get_oil_etf_symbol_array()


def qdii_get_qqq_match_array():
    return ['SH513100', 'SH513110', 'SH513390', 'SH513870', 'SZ159501',
            'SZ159513', 'SZ159632', 'SZ159659', 'SZ159660', 'SZ159696',
            'SZ159941', 'SZ161130']


def in_arrayQqqMatch(symbol):
    return symbol in qdii_get_qqq_match_array()


def qdii_get_qqq_symbol_array():
    return qdii_get_qqq_match_array() + ['SH513300']


def in_arrayQqqQdii(symbol):
    return symbol in qdii_get_qqq_symbol_array()


def qdii_get_spy_match_array():
    return ['SH513500', 'SH513650', 'SZ159612', 'SZ161125']


def in_arraySpyMatch(symbol):
    return symbol in qdii_get_spy_match_array()


def qdii_get_spy_symbol_array():
    return qdii_get_spy_match_array() + ['SZ159655']


def in_arraySpyQdii(symbol):
    return symbol in qdii_get_spy_symbol_array()


def qdii_get_xop_symbol_array():
    return ['SH513350', 'SZ159518', 'SZ162411']


def in_arrayXopQdii(symbol):
    return symbol in qdii_get_xop_symbol_array()


def qdii_get_xbi_symbol_array():
    return ['SZ159502', 'SZ161127']


def in_arrayXbiQdii(symbol):
    return symbol in qdii_get_xbi_symbol_array()


def qdii_get_symbol_array():
    return (['SH501300', 'SH513290', 'SH513400', 'SZ160140', 'SZ161126',
             'SZ161128', 'SZ162415', 'SZ164906']
            + qdii_get_xbi_symbol_array()
            + qdii_get_xop_symbol_array()
            + qdii_get_oil_etf_symbol_array()
            + qdii_get_qqq_symbol_array()
            + qdii_get_spy_symbol_array())


def in_arrayQdii(symbol):
    return symbol in qdii_get_symbol_array()


def qdii_hk_get_tech_symbol_array():
    return ['SH513010', 'SH513130', 'SH513180', 'SH513260', 'SH513380',
            'SH513580', 'SH513890', 'SH520570', 'SH520590', 'SH520920',
            'SZ159740', 'SZ159741', 'SZ159742']


def in_arrayTechQdiiHk(symbol):
    return symbol in qdii_hk_get_tech_symbol_array()


def qdii_hk_get_h_shares_symbol_array():
    return ['SH510900', 'SZ159850', 'SZ159954', 'SZ159960', 'SZ160717', 'SZ161831']


def in_arrayHSharesQdiiHk(symbol):
    return symbol in qdii_hk_get_h_shares_symbol_array()


def qdii_hk_get_hang_seng_symbol_array():
    return ['SH501302', 'SH513210', 'SH513600', 'SH513660', 'SZ159312',
            'SZ159920', 'SZ160924', 'SZ164705']


def in_arrayHangSengQdiiHk(symbol):
    return symbol in qdii_hk_get_hang_seng_symbol_array()


def qdii_hk_get_index_symbol_array():
    return ['^HSI', '^HSCE', '^HSTECH']


def qdii_hk_get_symbol_array():
    return (['SH501025', 'SZ161124']
            + qdii_hk_get_tech_symbol_array()
            + qdii_hk_get_h_shares_symbol_array()
            + qdii_hk_get_hang_seng_symbol_array())


def in_arrayQdiiHk(symbol):
    return symbol in qdii_hk_get_symbol_array()


def qdii_jp_get_nky_symbol_array():
    return ['SH513000', 'SH513520', 'SH513880', 'SZ159866']


def in_arrayNkyQdiiJp(symbol):
    return symbol in qdii_jp_get_nky_symbol_array()


def qdii_jp_get_symbol_array():
    return ['SH513800'] + qdii_jp_get_nky_symbol_array()


def in_arrayQdiiJp(symbol):
    return symbol in qdii_jp_get_symbol_array()


def qdii_eu_get_dax_symbol_array():
    return ['SH513030', 'SZ159561']


def in_arrayDaxQdiiEu(symbol):
    return symbol in qdii_eu_get_dax_symbol_array()


def qdii_eu_get_symbol_array():
    return ['SH513080'] + qdii_eu_get_dax_symbol_array()


def in_arrayQdiiEu(symbol):
    return symbol in qdii_eu_get_symbol_array()


def get_china_internet_symbol_array():
    return ['SH513050', 'SH513220', 'SZ159605', 'SZ159607']


def get_msci_us50_symbol_array():
    return ['SH513850', 'SZ159577']


def get_hk_mix_symbol_array():
    return ['SH513090', 'SH513230', 'SH513750', 'SH513990', 'SZ159567',
            'SZ159570', 'SZ159615', 'SZ159751', 'SZ159792']


def in_arrayHkMix(symbol):
    return symbol in get_hk_mix_symbol_array()


def get_qdii_gold_symbol_array():
    return ['SZ160216', 'SZ161815', 'SZ160719', 'SZ161116', 'SZ164701', 'SZ165513']


def get_qdii_oil_symbol_array():
    return ['SZ163208', 'SH501018', 'SZ160723', 'SZ161129']


def get_lof_mix_symbol_array():
    return (['SH501225', 'SH501312', 'SZ160644', 'SZ164824']
            + get_qdii_oil_symbol_array()
            + get_qdii_gold_symbol_array())


def in_arrayLofMix(symbol):
    return symbol in get_lof_mix_symbol_array()


def qdii_mix_get_symbol_array():
    return (['SH513360', 'SZ159509', 'SZ159529']
            + get_lof_mix_symbol_array()
            + get_china_internet_symbol_array()
            + get_hk_mix_symbol_array()
            + get_msci_us50_symbol_array())


def in_arrayQdiiMix(symbol):
    return symbol in qdii_mix_get_symbol_array()


def get_all_symbol_array():
    return (qdii_get_symbol_array()
            + qdii_mix_get_symbol_array()
            + qdii_hk_get_symbol_array()
            + qdii_jp_get_symbol_array()
            + qdii_eu_get_symbol_array()
            + china_index_get_symbol_array()
            + china_future_get_symbol_array())


def in_arrayAll(symbol):
    return symbol in get_all_symbol_array()


def get_over_night_symbol_array():
    return (qdii_get_xop_symbol_array()
            + ['SZ162719']
            + get_qdii_oil_symbol_array()
            + get_qdii_gold_symbol_array()
            + ['SZ161226', 'SZ161125', 'SZ161126', 'SZ161130', 'SZ162415',
               'SZ164824', 'SZ164906']
            + qdii_get_xbi_symbol_array())


# ---------------------------------------------------------------------------
# Digit range helpers (correspond to PHP _isDigit*())
# ---------------------------------------------------------------------------
def is_china_stock_digit(digit):
    """Corresponds to PHP ``IsChinaStockDigit()``."""
    if isinstance(digit, str) and len(digit) == 6 and digit.isdigit():
        return digit
    return None


def _is_digit_shenzhen_etf(i):
    return 150000 <= i <= 159999


def _is_digit_shenzhen_lof(i):
    return 160000 <= i <= 169999


def _is_digit_shenzhen_b(i):
    return 200000 <= i < 300000


def _is_digit_shenzhen_gem(i):  # 创业板 growth enterprise market, GEM
    return 300000 <= i < 390000


def _is_digit_shenzhen_index(i):
    return 390000 <= i < 400000


def _is_digit_shanghai_index(i):
    return 0 <= i < 100000


def _is_digit_shanghai_etf(i):
    return 510000 <= i <= 569999


def _is_digit_shanghai_lof(i):
    return 500000 <= i <= 509999


def _is_digit_shanghai_star(i):  # 科创板 SSE STAR Market
    return 688000 <= i <= 688999


def _is_digit_shanghai_b(i):
    return 900000 <= i < 1000000


def _is_digit_shenzhen_fund(i):
    return _is_digit_shenzhen_etf(i) or _is_digit_shenzhen_lof(i)


def _is_digit_shanghai_fund(i):
    return _is_digit_shanghai_etf(i) or _is_digit_shanghai_lof(i)


# ---------------------------------------------------------------------------
# Symbol builders
# ---------------------------------------------------------------------------
def build_china_fund_symbol(digit):
    """Corresponds to PHP ``BuildChinaFundSymbol()``."""
    if is_china_stock_digit(digit):
        i = int(digit)
        if _is_digit_shanghai_fund(i):
            prefix = SH_PREFIX
        elif _is_digit_shenzhen_fund(i):
            prefix = SZ_PREFIX
        else:
            return None
        return prefix + digit
    return None


def build_china_stock_symbol(digit):
    """Corresponds to PHP ``BuildChinaStockSymbol()``."""
    if is_china_stock_digit(digit):
        i = int(digit)
        if (i < 100000) or (200000 <= i < 400000):
            return SZ_PREFIX + digit
        elif (400000 <= i < 500000) or (800000 <= i < 900000):
            return BJ_PREFIX + digit
        elif i >= 600000:
            return SH_PREFIX + digit
    return None


def build_hongkong_stock_symbol(digit):
    """Corresponds to PHP ``BuildHongkongStockSymbol()``."""
    return digit.rjust(5, '0') if len(digit) != 5 else digit


def build_yahoo_net_value_symbol(symbol, suffix='IV'):
    """Corresponds to PHP ``BuildYahooNetValueSymbol()``."""
    if not symbol:
        return None
    return YAHOO_INDEX_CHAR + symbol + '-' + suffix


# ---------------------------------------------------------------------------
# StockSymbol class
# ---------------------------------------------------------------------------
class StockSymbol:
    def __init__(self, symbol):
        self.str_symbol = symbol
        self._first_char = None
        self._others = None
        self.str_digit_a = None     # e.g. '162411' or None
        self.i_digit_a = None
        self.str_prefix_a = None    # 'SH' / 'SZ' / 'BJ'
        self.i_digit_h = -1
        self._sina_index_h = None
        self._sina_index_us = None

    def _get_first_char(self):
        if self._first_char is None:
            self._first_char = self.str_symbol[:1]
            self._others = self.str_symbol[1:]

    def get_symbol(self):
        return self.str_symbol

    def is_index(self):
        self._get_first_char()
        return self._first_char == YAHOO_INDEX_CHAR

    def is_yahoo_net_value(self):
        if self.is_index():
            pos = self._others.find('-')
            if pos != -1:
                return self._others[:pos]
        return None

    def is_symbol_us(self):
        if self.is_symbol_a():
            return False
        if self.is_symbol_h():
            return False
        return True

    def is_symbol_h(self):
        if self.i_digit_h >= 0:
            return True
        symbol = self.str_symbol
        if self.is_index():
            if symbol in qdii_hk_get_index_symbol_array():
                self.i_digit_h = 0
                return True
        elif self._first_char == '0':
            if symbol.isdigit() and len(symbol) == 5:
                self.i_digit_h = int(symbol)
                return True
        return False

    def get_digit_a(self):
        return self.str_digit_a

    def is_symbol_a(self):
        if self.str_digit_a:
            return self.str_digit_a
        symbol = self.str_symbol
        digit = symbol[2:]
        if is_china_stock_digit(digit):
            prefix = symbol[:2].upper()
            if prefix in (SH_PREFIX, SZ_PREFIX, BJ_PREFIX):
                self.str_prefix_a = prefix
                self.i_digit_a = int(digit)
                self.str_digit_a = digit
                return digit
        return None

    def is_shanghai_a(self):
        if self.is_symbol_a():
            return self.str_prefix_a == SH_PREFIX
        return False

    def is_shenzhen_a(self):
        if self.is_symbol_a():
            return self.str_prefix_a == SZ_PREFIX
        return False

    def is_beijing_a(self):
        if self.is_symbol_a():
            return self.str_prefix_a == BJ_PREFIX
        return False

    def is_shanghai_etf(self):
        if self.is_shanghai_a():
            return _is_digit_shanghai_etf(self.i_digit_a)
        return False

    def is_shanghai_lof(self):
        if self.is_shanghai_a():
            return _is_digit_shanghai_lof(self.i_digit_a)
        return False

    def is_shanghai_star(self):
        if self.is_shanghai_a():
            return _is_digit_shanghai_star(self.i_digit_a)
        return False

    def is_shanghai_b(self):
        if self.is_shanghai_a():
            if _is_digit_shanghai_b(self.i_digit_a):
                return self.str_digit_a
        return None

    def is_shenzhen_b(self):
        if self.is_shenzhen_a():
            if _is_digit_shenzhen_b(self.i_digit_a):
                return self.str_digit_a
        return None

    def is_shenzhen_etf(self):
        if self.is_shenzhen_a():
            return _is_digit_shenzhen_etf(self.i_digit_a)
        return False

    def is_shenzhen_lof(self):
        if self.is_shenzhen_a():
            return _is_digit_shenzhen_lof(self.i_digit_a)
        return False

    def is_shenzhen_gem(self):
        if self.is_shenzhen_a():
            return _is_digit_shenzhen_gem(self.i_digit_a)
        return False

    def is_lof_a(self):
        if self.is_shenzhen_lof():
            return True
        if self.is_shanghai_lof():
            return True
        return False

    def is_etf_a(self):
        if self.is_shenzhen_etf():
            return True
        if self.is_shanghai_etf():
            return True
        return False

    def is_fund_a(self):
        if self.is_lof_a() or self.is_etf_a():
            return self.str_digit_a
        return None

    def is_index_a(self):
        if not self.str_digit_a:
            if not self.is_symbol_a():
                return None
        if self.str_prefix_a == SZ_PREFIX and _is_digit_shenzhen_index(self.i_digit_a):
            return self.str_digit_a
        if self.str_prefix_a == SH_PREFIX and _is_digit_shanghai_index(self.i_digit_a):
            return self.str_digit_a
        return None

    def is_stock_b(self):
        if self.is_shanghai_b():
            return True
        if self.is_shenzhen_b():
            return True
        return False

    def is_east_money_forex(self):
        return self.str_symbol in ('USCNY', 'EUCNY', 'JPCNY', 'HKCNY')

    def is_sina_forex(self):
        remainder = str_has_prefix(self.str_symbol, SINA_FOREX_PREFIX)
        return remainder.upper() if remainder is not None else None

    def is_forex(self):
        if self.is_east_money_forex():
            return True
        if self.is_sina_forex():
            return True
        return False

    def is_sina_fund(self):
        digit = str_has_prefix(self.str_symbol, SINA_FUND_PREFIX)
        if digit is not None:
            return is_china_stock_digit(digit)
        return None

    def is_sina_global_index(self):
        return str_has_prefix(self.str_symbol, SINA_INDEX_PREFIX)

    def is_sina_future_cn(self):
        return str_has_prefix(self.str_symbol, SINA_CN_FUTURE_PREFIX)

    def is_sina_future_except_gold_cn(self):
        s = self.is_sina_future_cn()
        if s is not None:
            if s != 'AU0':
                return True
        return False

    def is_sina_future_us(self):
        return str_has_prefix(self.str_symbol, SINA_FUTURE_PREFIX)

    def is_sina_future(self):
        if self.is_sina_future_cn() is not None:
            return True
        if self.is_sina_future_us() is not None:
            return True
        return False

    def get_sina_fund_symbol(self):
        if self.is_fund_a():
            return SINA_FUND_PREFIX + self.str_digit_a
        return None

    def get_sina_index_h(self):
        if self._sina_index_h is None:
            mapping = {'^HSI': 'HSI', '^HSCE': 'HSCEI', '^HSTECH': 'HSTECH'}
            self._sina_index_h = mapping.get(self.str_symbol)
        return self._sina_index_h

    def get_sina_index_us(self):
        if self._sina_index_us is None:
            mapping = {'^DJI': 'dji', '^GSPC': 'inx', '^NDX': 'ndx'}
            self._sina_index_us = mapping.get(self.str_symbol)
        return self._sina_index_us

    def get_sina_symbol(self):
        if self.is_sina_global_index() is not None:
            return self.str_symbol

        symbol = self.str_symbol.replace('.', '$')
        lower = symbol.lower()
        if self.is_index():
            if self.get_sina_index_h():
                return SINA_HK_PREFIX + self._sina_index_h
            elif self.get_sina_index_us():
                return SINA_US_PREFIX + self._sina_index_us
            else:
                return None
        elif self.is_symbol_h():
            return SINA_HK_PREFIX + symbol
        elif self.is_symbol_a():
            return lower
        return SINA_US_PREFIX + lower

    def get_yahoo_symbol(self):
        future_suffix = '%3DF'   # CL=F
        index_prefix = '%5E'     # ^HSI
        hk = '.hk'

        symbol = self.str_symbol.replace('.', '-')
        s = self.is_sina_future_us()
        if s is not None:
            if s == 'CHA50CFD':
                return 'XIN9.FGI'
            return s + future_suffix   # CL=F

        s = self.is_sina_forex()
        if s is not None:
            if s == 'USDCNH':
                return 'CNH' + future_suffix   # CNH=F

        s = self.is_sina_global_index()
        if s is not None:
            mapping = {'CAC': index_prefix + 'FCHI',
                       'DAX': index_prefix + 'GDAXI',
                       'NKY': index_prefix + 'N225',
                       'SENSEX': index_prefix + 'BSESN'}
            if s in mapping:
                return mapping[s]
        elif self.is_index():
            if symbol == '^HSTECH':
                return self._others + hk
            return index_prefix + self._others   # index ^HSI
        elif self.is_symbol_h():
            return self._others + hk   # Hongkong market
        elif self.is_symbol_a():
            if self.str_prefix_a == SH_PREFIX:
                return self.str_digit_a + '.ss'   # Shanghai market
            elif self.str_prefix_a == SZ_PREFIX:
                return self.str_digit_a + '.sz'   # Shenzhen market
            elif self.str_prefix_a == BJ_PREFIX:
                return self.str_digit_a + '.bj'   # Beijing market
        return symbol

    def get_precision(self):
        if self.is_fund_a() or self.is_sina_fund() or self.is_stock_b():
            return 3
        elif self.is_forex():
            return 4
        return 2

    def get_default_position(self):
        return 0.95 if self.is_lof_a() else 1.0

    def is_tradable(self):
        if self.is_sina_global_index() is not None:
            return False
        if self.is_index():
            return False
        if self.is_index_a():
            return False
        return True

    def get_display(self):
        s = self.is_sina_future_us()
        if s is not None:
            return s
        s = self.is_sina_future_cn()
        if s is not None:
            return s
        s = self.is_sina_forex()
        if s is not None:
            return s
        s = self.is_sina_global_index()
        if s is not None:
            return s
        return self.get_symbol()
