
def calculate_annualized_return(principal, total_return, years):
    # 计算最终总金额
    final_amount = principal + total_return
    
    # 计算年化收益率公式：final_amount = principal * (1 + rate)^years
    # 解方程：rate = (final_amount / principal)^(1/years) - 1
    rate = (final_amount / principal) ** (1 / years) - 1
    
    return rate * 100  # 转换为百分比

def main():
    import sys
    print(f"Hello, World! {sys.version}")

    principal = 350  # 350万元本金
    total_return = 168  # 168万元总收益
    years = 10  # 10年
    result = calculate_annualized_return(principal, total_return, years)
    
    print("\n" + "=" * 50)
    print(f"总结：无敌哥10年赚168万，本金350万，年化收益率为：{result:.4f}%")

main()
