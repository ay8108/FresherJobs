@if($paginator->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="source">
            Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results
        </span>
        <nav aria-label="Job pagination">
            <ul class="pagination pagination-sm mb-0">
                {{-- Previous Button --}}
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    <button type="button" class="page-link" data-page="{{ $paginator->currentPage() - 1 }}" aria-label="Previous page">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                </li>

                {{-- Page number window --}}
                @php
                    $current = $paginator->currentPage();
                    $last = $paginator->lastPage();
                    $start = max(1, $current - 2);
                    $end = min($last, $current + 2);
                    if ($current <= 3) $end = min($last, 5);
                    if ($current >= $last - 2) $start = max(1, $last - 4);
                @endphp

                @if($start > 1)
                    <li class="page-item"><button type="button" class="page-link" data-page="1">1</button></li>
                    @if($start > 2)
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    @endif
                @endif

                @for($i = $start; $i <= $end; $i++)
                    <li class="page-item {{ $i == $current ? 'active' : '' }}">
                        <button type="button" class="page-link" data-page="{{ $i }}" {{ $i == $current ? 'aria-current="page"' : '' }}>
                            {{ $i }}
                        </button>
                    </li>
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    @endif
                    <li class="page-item"><button type="button" class="page-link" data-page="{{ $last }}">{{ $last }}</button></li>
                @endif

                {{-- Next Button --}}
                <li class="page-item {{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
                    <button type="button" class="page-link" data-page="{{ $paginator->currentPage() + 1 }}" aria-label="Next page">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </li>
            </ul>
        </nav>
    </div>
@endif
