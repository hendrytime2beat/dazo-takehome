package analytics

import (
	"sort"
	"time"
)

// Order is the minimal order shape Laravel sends over for aggregation.
type Order struct {
	CreatedAt time.Time `json:"created_at"`
	Status    string    `json:"status"`
	Total     float64   `json:"total_amount"`
	Shipping  float64   `json:"shipping_cost"`
}

// OrderItem is the minimal order-item shape used for top-product ranking.
type OrderItem struct {
	ProductName string  `json:"product_name"`
	Quantity    int     `json:"quantity"`
	LineTotal   float64 `json:"line_total"`
}

// Payload is the request body accepted by the analytics endpoint.
type Payload struct {
	Orders     []Order     `json:"orders"`
	OrderItems []OrderItem `json:"order_items"`
}

// Summary holds the headline dashboard metrics.
type Summary struct {
	TotalOrders       int     `json:"total_orders"`
	TotalRevenue      float64 `json:"total_revenue"`
	TotalShippingCost float64 `json:"total_shipping_cost"`
	PaidOrders        int     `json:"paid_orders"`
	UnpaidOrders      int     `json:"unpaid_orders"`
	PaidPercentage    float64 `json:"paid_percentage"`
	UnpaidPercentage  float64 `json:"unpaid_percentage"`
	AverageOrderValue float64 `json:"average_order_value"`
	TotalItemsSold    int     `json:"total_items_sold"`
	PaidTotal         float64 `json:"paid_total"`
	UnpaidTotal       float64 `json:"unpaid_total"`
	NetRevenue        float64 `json:"net_revenue"`
	Profit            float64 `json:"profit"`
}

// SeriesPoint is one bucket in the daily sales series.
type SeriesPoint struct {
	Date    string  `json:"date"`
	Revenue float64 `json:"revenue"`
	Orders  int     `json:"orders"`
}

// TopProduct is a ranked product by quantity sold.
type TopProduct struct {
	Name     string  `json:"name"`
	Quantity int     `json:"quantity"`
	Revenue  float64 `json:"revenue"`
}

// Result is the full analytics response returned to Laravel.
type Result struct {
	Summary      Summary            `json:"summary"`
	SalesSeries  []SeriesPoint      `json:"sales_series"`
	PaymentSplit map[string]float64 `json:"payment_split"`
	TopProducts  []TopProduct       `json:"top_products"`
}

// Compute aggregates the given orders/items into a Result.
func Compute(payload Payload) Result {
	var (
		totalOrders, paidOrders, paidItems int
		totalRevenue, totalShipping        float64
		itemRevenue                        float64
	)

	byDate := map[string]*SeriesPoint{}
	paidSplit := float64(0)
	unpaidSplit := float64(0)

	for _, o := range payload.Orders {
		totalOrders++
		totalRevenue += o.Total
		totalShipping += o.Shipping

		switch o.Status {
		case "paid":
			paidOrders++
			paidSplit += o.Total
		case "pending":
			unpaidSplit += o.Total
		}

		key := o.CreatedAt.In(time.UTC).Format("2006-01-02")
		pt, ok := byDate[key]
		if !ok {
			pt = &SeriesPoint{Date: key}
			byDate[key] = pt
		}
		pt.Revenue += o.Total
		pt.Orders++
	}

	unpaidOrders := totalOrders - paidOrders
	var paidPct, unpaidPct float64
	if totalOrders > 0 {
		paidPct = float64(paidOrders) / float64(totalOrders) * 100
		unpaidPct = float64(unpaidOrders) / float64(totalOrders) * 100
	}

	var avg float64
	if totalOrders > 0 {
		avg = totalRevenue / float64(totalOrders)
	}

	series := make([]SeriesPoint, 0, len(byDate))
	for _, pt := range byDate {
		series = append(series, *pt)
	}
	sort.Slice(series, func(i, j int) bool { return series[i].Date < series[j].Date })

	productQty := map[string]*TopProduct{}
	for _, it := range payload.OrderItems {
		tp, ok := productQty[it.ProductName]
		if !ok {
			tp = &TopProduct{Name: it.ProductName}
			productQty[it.ProductName] = tp
		}
		tp.Quantity += it.Quantity
		tp.Revenue += it.LineTotal
		paidItems += it.Quantity
		itemRevenue += it.LineTotal
	}

	// Perkiraan "Laba Kotor": asumsi margin kotor 40% dari nilai items
	// (COGS 60%). Shipping tidak termasuk HPP; didokumentasikan di README.
	const grossMargin = 0.4
	netRevenue := totalRevenue - totalShipping
	profit := itemRevenue * grossMargin

	top := make([]TopProduct, 0, len(productQty))
	for _, tp := range productQty {
		top = append(top, *tp)
	}
	sort.Slice(top, func(i, j int) bool { return top[i].Quantity > top[j].Quantity })
	if len(top) > 5 {
		top = top[:5]
	}

	return Result{
		Summary: Summary{
			TotalOrders:       totalOrders,
			TotalRevenue:      round2(totalRevenue),
			TotalShippingCost: round2(totalShipping),
			PaidOrders:        paidOrders,
			UnpaidOrders:      unpaidOrders,
			PaidPercentage:    round2(paidPct),
			UnpaidPercentage:  round2(unpaidPct),
			AverageOrderValue: round2(avg),
			TotalItemsSold:    paidItems,
			PaidTotal:         round2(paidSplit),
			UnpaidTotal:       round2(unpaidSplit),
			NetRevenue:        round2(netRevenue),
			Profit:            round2(profit),
		},
		SalesSeries: series,
		PaymentSplit: map[string]float64{
			"paid":    round2(paidSplit),
			"pending": round2(unpaidSplit),
		},
		TopProducts: top,
	}
}

func round2(v float64) float64 {
	return float64(int(v*100+0.5)) / 100
}
