from datetime import datetime, timezone, timedelta

"""
from datetime import datetime
from zoneinfo import ZoneInfo
def GetExchangeTime(strName = 'NYSE'):
    exchange_timezones = {'NYSE': 'America/New_York','SZSE': 'Asia/Shanghai'}
    if strName not in exchange_timezones:
        raise ValueError(f'Unsupported exchange: {strName}')
    now_exchange = datetime.now(ZoneInfo(exchange_timezones[strName]))
    return now_exchange.hour * 100 + now_exchange.minute
"""

# Check if the current date is within daylight saving time period for Eastern Time Zone (2nd Sunday of March to 1st Sunday of November)
def is_daylight_saving_time(dt, utc):
    start_dst = datetime(dt.year, 3, 8, 2, 0, tzinfo=utc)  # 2nd Sunday of March
    end_dst = datetime(dt.year, 11, 1, 2, 0, tzinfo=utc)  # 1st Sunday of November
    return start_dst <= dt < end_dst

def GetExchangeTime(strName = 'NYSE'):
    # Define the UTC timezone
    utc = timezone.utc
    # Get the current UTC time
    now_utc = datetime.now(utc)
    if strName == 'NYSE':
        iHours = -5
        # Determine the Eastern Time Zone offset considering daylight saving time
        if is_daylight_saving_time(now_utc, utc):
            #eastern_offset = timedelta(hours=-4)  # Eastern Daylight Time (EDT) offset
            iHours += 1
    elif strName == 'SZSE':
        iHours = 8
    eastern_offset = timedelta(hours=iHours)  # Eastern Standard Time (EST) offset
    # Convert UTC time to Eastern Time Zone
    now_ny = now_utc.astimezone(timezone(eastern_offset))
    # Extract the hour and minute in 'America/New_York' time zone
    iTime = now_ny.hour * 100 + now_ny.minute 
    return iTime

"""
def get_nth_weekday_of_month(year, month, weekday, n):
    #返回 year年 month月 第 n个 weekday 的日期
    first = datetime(year, month, 1)
    days_ahead = weekday - first.weekday()
    if days_ahead < 0:
        days_ahead += 7
    return first + timedelta(days=days_ahead + (n - 1) * 7)

def is_daylight_saving_time(dt):
    #美国夏令时：3月第二个周日 ~ 11月第一个周日
    start_dst = get_nth_weekday_of_month(dt.year, 3, 6, 2).replace(hour=2, tzinfo=timezone.utc)
    end_dst = get_nth_weekday_of_month(dt.year, 11, 6, 1).replace(hour=2, tzinfo=timezone.utc)
    return start_dst <= dt < end_dst
"""
