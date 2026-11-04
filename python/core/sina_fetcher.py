"""Sina data fetching and parsing layer.

Migration of:
  * ``php/stock/stockref.php``      (the ``_onSinaData*`` parsers + ``LoadSina*Data``)
  * ``php/stock/stockprefetch.php`` (the batch fetching logic)
  * ``php/stock.php``               (``GetSinaQuotes`` / ``explodeQuote``)

Field indices correspond exactly to the PHP implementation.  All parser methods
return plain ``dict``s with raw string field values (matching how PHP stores the
raw ``ar`` values; ``rtrim0`` style trimming is applied by callers when needed).
"""

import datetime

import requests

try:
    from cachetools import TTLCache
except ImportError:  # pragma: no cover - cachetools is an optional dependency
    TTLCache = None

try:
    from .stock_symbol import StockSymbol
except ImportError:  # pragma: no cover - allow running as a script
    from stock_symbol import StockSymbol


SINA_DATA_URL = 'http://hq.sinajs.cn/list='
SINA_REFERER = 'https://finance.sina.com.cn'

# 60 second TTL, corresponds to the PHP per-minute file cache.
if TTLCache is not None:
    _sina_cache = TTLCache(maxsize=500, ttl=60)
else:  # pragma: no cover
    _sina_cache = {}


def rtrim0(s):
    """Corresponds to PHP ``rtrim0()`` => ``strval(floatval($str))``."""
    if s is None or s == '':
        return s
    try:
        f = float(s)
    except (TypeError, ValueError):
        return s
    if f == int(f):
        return str(int(f))
    return repr(f)


def _to_float(s, default=0.0):
    try:
        return float(s)
    except (TypeError, ValueError):
        return default


# ---------------------------------------------------------------------------
# Sina data parsers (correspond to StockReference::_onSinaData* / Load*Data)
# ---------------------------------------------------------------------------
class SinaDataParser:
    @staticmethod
    def parse_cn(fields):
        """Corresponds to ``_onSinaDataCN()``."""
        return {
            'name': fields[0],
            'open': fields[1],
            'prev_price': fields[2],
            'price': fields[3],
            'high': fields[4],
            'low': fields[5],
            'volume': fields[8],
            'date': fields[30],
            'time': fields[31],
            'bid_quantity': [fields[i] for i in range(10, 20, 2)],
            'bid_price':    [fields[i] for i in range(11, 21, 2)],
            'ask_quantity': [fields[i] for i in range(20, 30, 2)],
            'ask_price':    [fields[i] for i in range(21, 31, 2)],
        }

    @staticmethod
    def _convert_datetime_from_us(datetime_str, year):
        """Corresponds to ``_convertDateTimeFromUS()``.

        ``datetime_str`` is e.g. ``'Jun 08 11:36AM EDT'`` and ``year`` is the
        4 digit year string.  PHP parses it in America/New_York; the displayed
        time therefore equals the parsed local time.
        """
        cleaned = datetime_str.strip()
        # Strip a trailing timezone abbreviation (e.g. 'EDT'/'EST').
        parts = cleaned.rsplit(' ', 1)
        if len(parts) == 2 and parts[1].isalpha():
            cleaned = parts[0]
        for fmt in ('%b %d %I:%M%p', '%b %d %I:%M %p'):
            try:
                dt = datetime.datetime.strptime(cleaned, fmt)
                date = '%s-%02d-%02d' % (year, dt.month, dt.day)
                return date, dt.strftime('%H:%M:%S')
            except ValueError:
                continue
        return datetime_str, ''

    @staticmethod
    def parse_us(fields):
        """Corresponds to ``_onSinaDataUS()``."""
        date, time = SinaDataParser._convert_datetime_from_us(fields[25], fields[29])
        return {
            'name': fields[0],
            'price': fields[1],
            'prev_price': fields[26],
            'date': date,
            'time': time,
            'open': fields[5],
            'high': fields[6],
            'low': fields[7],
            'volume': fields[10],
        }

    @staticmethod
    def parse_hk(fields):
        """Corresponds to ``_onSinaDataHK()``."""
        return {
            'name': fields[0],
            'chinese_name': fields[1],
            'prev_price': fields[3],
            'price': fields[6],
            'date': fields[17].replace('/', '-'),   # 2016/03/02
            'time': fields[18],
            'open': fields[2],
            'high': fields[4],
            'low': fields[5],
            'volume': fields[12],
        }

    @staticmethod
    def parse_future_us(fields):
        """Corresponds to ``_onSinaFuture()``."""
        return {
            'name': fields[13],
            'price': fields[0],
            'prev_price': fields[7],
            'date': fields[12],
            'time': fields[6],
            'open': fields[8],
            'high': fields[4],
            'low': fields[5],
        }

    @staticmethod
    def parse_future_cn(fields):
        """Corresponds to ``_onSinaFutureCN()``."""
        raw_time = fields[1]
        time = raw_time[0:2] + ':' + raw_time[2:4] + ':' + raw_time[4:6]
        settle = fields[9] if not _is_zero_string(fields[9]) else None
        return {
            'name': fields[15] + '-' + fields[0],
            'price': fields[8],
            'prev_price': fields[10],
            'date': fields[17],
            'time': time,
            'open': fields[2],
            'high': fields[3],
            'low': fields[4],
            'volume': fields[14],
            'settle_price': settle,
            'vwap': fields[27],
        }

    @staticmethod
    def parse_forex(fields, symbol=None):
        """Corresponds to ``LoadSinaForexData()``."""
        price = fields[8]
        prev_price = fields[3]
        if symbol == 'fx_sjpycny':
            price = repr(_to_float(price) * 100.0)
            prev_price = repr(_to_float(prev_price) * 100.0)
        return {
            'name': fields[9],
            'price': price,
            'prev_price': prev_price,
            'date': fields[-1],   # PHP end($ar)
            'time': fields[0],
            'open': fields[5],
            'high': fields[6],
            'low': fields[7],
        }

    @staticmethod
    def parse_fund(fields):
        """Corresponds to ``LoadSinaFundData()``."""
        return {
            'name': fields[0],
            'price': fields[1],         # net value
            'prev_price': fields[3],
            'date': fields[4],
        }

    @staticmethod
    def parse_global_index(fields, count):
        """Corresponds to ``_onSinaGlobalIndex()``."""
        data = {
            'name': fields[0],
            'price': fields[1],
        }
        if count == 6:
            data['prev_price'] = repr(_to_float(fields[1]) - _to_float(fields[2]))
            tick = int(fields[5])
            dt = datetime.datetime.fromtimestamp(tick)
            data['date'] = dt.strftime('%Y-%m-%d')
            data['time'] = dt.strftime('%H:%M:%S')
        else:
            data['prev_price'] = fields[9]
            data['date'] = fields[6]
            data['time'] = fields[7]
            data['open'] = fields[8]
            data['high'] = fields[10]
            data['low'] = fields[11]
        return data


def _is_zero_string(s):
    """Corresponds to PHP ``IsZeroString()``."""
    return abs(_to_float(s)) < 0.0000005


# ---------------------------------------------------------------------------
# Sina data fetcher (corresponds to GetSinaQuotes + _prefetchSinaData)
# ---------------------------------------------------------------------------
def _parse_sina_response(text):
    """Parse Sina's ``var hq_str_xxx="...";`` lines.

    Equivalent to PHP ``explodeQuote()`` applied per line, returning
    ``{sina_symbol: [field, ...]}``.
    """
    prefix = 'var hq_str_'
    result = {}
    for line in text.split('\n'):
        if not line.startswith(prefix):
            continue
        rest = line[len(prefix):]
        eq = rest.find('=')
        if eq < 0:
            continue
        symbol = rest[:eq]
        q1 = rest.find('"', eq)
        if q1 < 0:
            continue
        q2 = rest.find('"', q1 + 1)
        content = rest[q1 + 1:q2] if q2 >= 0 else rest[q1 + 1:]
        result[symbol] = content.split(',')
    return result


def fetch_sina_quotes(symbols, use_cache=True, timeout=10):
    """Batch fetch Sina quotes.

    Corresponds to ``GetSinaQuotes()`` + ``_prefetchSinaData()``.  Returns
    ``{sina_symbol: [field, ...]}`` (the raw field lists, as produced by
    ``explodeQuote`` per line).  Sina returns GB2312 encoded data
    (``bConvertGB2312`` in PHP), so the response encoding is forced.
    """
    symbols = [s for s in symbols if s]
    result = {}
    to_fetch = []
    for s in symbols:
        if use_cache and s in _sina_cache:
            result[s] = _sina_cache[s]
        else:
            to_fetch.append(s)

    if to_fetch:
        url = SINA_DATA_URL + ','.join(to_fetch)
        response = requests.get(url, headers={'Referer': SINA_REFERER}, timeout=timeout)
        response.raise_for_status()
        response.encoding = 'gb2312'
        parsed = _parse_sina_response(response.text)
        for s, fields in parsed.items():
            if use_cache:
                _sina_cache[s] = fields
            result[s] = fields
    return result


def _resolve_sina_symbol(sym):
    """Return the Sina list symbol for a :class:`StockSymbol`.

    Forex / future / fund / global-index symbols are already in Sina format, so
    they are used verbatim; other symbols are translated via ``GetSinaSymbol``.
    """
    if (sym.is_sina_global_index() is not None or sym.is_sina_future()
            or sym.is_sina_forex() or sym.is_sina_fund() is not None):
        return sym.get_symbol()
    return sym.get_sina_symbol()


def parse_fields(symbol, fields):
    """Dispatch raw ``fields`` to the correct parser based on ``symbol`` type.

    Corresponds to the type dispatch inside ``StockReference::LoadSinaData()``
    and the dedicated ``LoadSina*Data`` loaders.
    """
    sym = StockSymbol(symbol)
    count = len(fields)
    if sym.is_sina_global_index() is not None:
        return SinaDataParser.parse_global_index(fields, count)
    if sym.is_sina_future_us() is not None:
        return SinaDataParser.parse_future_us(fields)
    if sym.is_sina_future_cn() is not None:
        return SinaDataParser.parse_future_cn(fields)
    if sym.is_sina_forex():
        return SinaDataParser.parse_forex(fields, symbol)
    if sym.is_sina_fund() is not None:
        return SinaDataParser.parse_fund(fields)
    if sym.is_symbol_a():
        return SinaDataParser.parse_cn(fields)
    if sym.is_symbol_h():
        return SinaDataParser.parse_hk(fields)
    return SinaDataParser.parse_us(fields)


def load_stock_data(symbol, use_cache=True):
    """Load a single symbol's parsed data.

    Corresponds to ``StockReference::LoadSinaData()`` (and the future/forex/fund
    variants).  Returns the parsed ``dict`` or ``None`` when no data is found.
    """
    sym = StockSymbol(symbol)
    sina_symbol = _resolve_sina_symbol(sym)
    if not sina_symbol:
        return None
    raw = fetch_sina_quotes([sina_symbol], use_cache=use_cache)
    fields = raw.get(sina_symbol)
    if not fields or len(fields) < 4:
        return None
    return parse_fields(symbol, fields)
