// table_filter.js - Live Filtering and Search Utility
document.addEventListener('DOMContentLoaded', function () {
  const filterCategory = document.getElementById('filterCategory');
  const liveSearchInput = document.getElementById('liveSearchInput');
  const resetFilterBtn = document.getElementById('resetFilterBtn');
  const visibleCountSpan = document.getElementById('visibleCount');

  function applyLiveFilter() {
    const category = filterCategory ? filterCategory.value : 'all';
    const query = liveSearchInput ? liveSearchInput.value.toLowerCase().trim() : '';
    const rows = document.querySelectorAll('tbody tr.data-row');
    const noMatchesRow = document.getElementById('noMatchesRow');
    let visibleCount = 0;

    rows.forEach(row => {
      let targetText = '';

      if (category === 'all') {
        targetText = row.textContent.toLowerCase();
      } else {
        const targetCell = row.querySelector('.col-' + category);
        targetText = targetCell ? targetCell.textContent.toLowerCase() : row.textContent.toLowerCase();
      }

      if (targetText.includes(query)) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (visibleCountSpan) {
      visibleCountSpan.textContent = visibleCount;
    }

    if (noMatchesRow) {
      noMatchesRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
  }

  if (liveSearchInput) liveSearchInput.addEventListener('input', applyLiveFilter);
  if (filterCategory) filterCategory.addEventListener('change', applyLiveFilter);

  if (resetFilterBtn) {
    resetFilterBtn.addEventListener('click', function () {
      if (filterCategory) filterCategory.value = 'all';
      if (liveSearchInput) liveSearchInput.value = '';
      applyLiveFilter();
    });
  }
});