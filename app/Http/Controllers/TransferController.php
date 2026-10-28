<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\InventoryService;
use App\Services\SequenceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected SequenceService $sequenceService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $transfers = StockTransfer::where('business_id', $user->business_id)
            ->with(['sourceBranch', 'destinationBranch', 'user', 'items.product'])
            ->latest()
            ->paginate(15);

        return view('transfers.index', compact('transfers'));
    }

    public function create()
    {
        $user = Auth::user();
        $branches = Branch::where('business_id', $user->business_id)->where('is_active', true)->get();
        $products = Product::where('business_id', $user->business_id)->where('is_active', true)->with('baseUnit')->get();

        return view('transfers.create', compact('branches', 'products'));
    }

    public function store(TransferRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $businessId = $user->business_id;

        $sourceBranch = Branch::findOrFail((int) $data['source_branch_id']);
        $destBranch = Branch::findOrFail((int) $data['destination_branch_id']);

        return DB::transaction(function () use ($businessId, $sourceBranch, $destBranch, $user, $data) {
            $transferNumber = $this->sequenceService->getNextNumber(
                $businessId,
                $sourceBranch->id,
                'transfer'
            );

            $transfer = StockTransfer::create([
                'business_id' => $businessId,
                'source_branch_id' => $sourceBranch->id,
                'destination_branch_id' => $destBranch->id,
                'user_id' => $user->id,
                'transfer_number' => $transferNumber,
                'transfer_date' => $data['transfer_date'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);
                $qty = (float) $item['quantity'];

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => (float) $product->purchase_price,
                    'total_cost' => round($qty * $product->purchase_price, 2),
                ]);
            }

            // Execute the stock transfer atomically
            $this->inventoryService->executeTransfer($transfer);

            AuditLog::log('stock_transfer_completed', $transfer, null, ['transfer_number' => $transferNumber]);

            return redirect()->route('transfers.show', $transfer->id)->with('success', "Transfer {$transferNumber} completed successfully!");
        });
    }

    public function show(int $id)
    {
        $transfer = StockTransfer::with(['sourceBranch', 'destinationBranch', 'items.product.baseUnit', 'user'])
            ->findOrFail($id);

        return view('transfers.show', compact('transfer'));
    }
}
