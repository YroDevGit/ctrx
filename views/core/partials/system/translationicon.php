<?php
include_once "app/php/core/partials/backend.php";
$data = [];
$emessage = "";
$success = false;
try {
  $data = \Classes\DB::query("select * from translations group by lang");
  $success = true;
} catch (Exception $e) {
  $data = [];
  $emessage = $e->getMessage();
}
?>

<div id="transFloatWidget" style="
  position: fixed;
  z-index: 2147483647;
  font-family: 'Segoe UI', system-ui, -apple-system, 'Inter', sans-serif;
">
  <div id="transCircleBtn" style="
    width: 33px;
    height: 33px;
    background: linear-gradient(135deg, #1a73e8, #0d47a1);
    border-radius: 50%;
    box-shadow: 0 6px 16px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.2);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    backdrop-filter: blur(4px);
    touch-action: none;
  ">
    <svg width="23" height="23" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
      <path d="M8 3H9C7.5 7 7 12 9 16H8" stroke="white" stroke-width="1.5" fill="none" />
      <path d="M15 3C16.5 7 17 12 15 16" stroke="white" stroke-width="1.5" fill="none" />
      <path d="M3 16H21" stroke="white" stroke-width="1.5" stroke-linecap="round" />
      <path d="M3 8H21" stroke="white" stroke-width="1.5" stroke-linecap="round" />
      <path d="M12 2V22" stroke="white" stroke-width="1.5" />
    </svg>
  </div>

  <div id="transExpandedPanel" style="
    position: fixed;
    width: 300px;
    background: rgba(20, 20, 28, 0.96);
    backdrop-filter: blur(16px);
    border-radius: 28px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.15);
    overflow: hidden;
    transition: all 0.25s cubic-bezier(0.34, 1.2, 0.64, 1);
    transform: scale(0.95);
    opacity: 0;
    pointer-events: none;
    border: 1px solid rgba(255,255,255,0.2);
  ">
    <div id="expandedDragHandle" style="
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 18px;
      background: rgba(0, 0, 0, 0.4);
      border-bottom: 1px solid rgba(255, 255, 255, 0.15);
      cursor: grab;
      touch-action: none;
    ">
      <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.9rem; color: white;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
          <path d="M8 3H9C7.5 7 7 12 9 16H8" stroke="white" stroke-width="1.5" fill="none" />
          <path d="M15 3C16.5 7 17 12 15 16" stroke="white" stroke-width="1.5" fill="none" />
          <path d="M3 16H21" stroke="white" stroke-width="1.5" stroke-linecap="round" />
          <path d="M3 8H21" stroke="white" stroke-width="1.5" stroke-linecap="round" />
          <path d="M12 2V22" stroke="white" stroke-width="1.5" />
        </svg>
        <span>Ctrx Translate</span>
      </div>
      <div style="display: flex; gap: 8px;">
        <button id="collapseBtn" style="background: rgba(255,255,255,0.2); border: none; border-radius: 30px; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; color: white; cursor: pointer;">−</button>
        <button id="closePanelBtn" style="background: rgba(255,255,255,0.2); border: none; border-radius: 30px; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold; color: white; cursor: pointer;">✕</button>
      </div>
    </div>

    <div style="padding: 16px 18px 20px; max-height: 360px; overflow-y: auto; background: rgba(10, 10, 14, 0.5);">
      <?php if ($success): ?>
        <?php
          $backg_col = " background: rgba(255,255,255,0.1); color: #f0f0f0;";
          if(! isset($_SESSION['ctrx_translate']) || ! $_SESSION['ctrx_translate']){
            $backg_col = " background: #efefb2; color: black;";
          }
          ?>
        <div style="display: flex; flex-direction: column; gap: 10px;">
          <a href="<?= array_as_param([...$_GET, 'ctrx_translate'=> ''])?>" class="transItem" rel="noopener noreferrer" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 20px; text-decoration: none; font-weight: 500; font-size: 0.85rem; transition: all 0.2s;<?=$backg_col?>">
            <span style="font-size: 1.2rem;"></span>
            <span style="flex:1;">Default</span>
            <span style="opacity:0.6;">→</span>
          </a>
          <?php foreach ($data as $key => $val): ?>
            <?php
            $name = $val['name'] ?? $val['lang'] ?? "Unknown";
            $lang = $val['lang'] ?? null;
              $bg_col = " background: rgba(255,255,255,0.1); color: #f0f0f0;";
              if($lang && is_string($lang) && isset($_SESSION['ctrx_translate'])){
                if($lang == $_SESSION['ctrx_translate']){
                  $bg_col = " background: #efefb2; color: black;";
                }
              }
            if($name == "" || $name == null){
              $name = $val['lang'] ?? "Unknown";
            }
            ?>
            <a href="<?= array_as_param([...$_GET, 'ctrx_translate'=>$val['lang'] ?? ''])?>" class="transItem" rel="noopener noreferrer" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 20px; text-decoration: none; font-weight: 500; font-size: 0.85rem; transition: all 0.2s;<?=$bg_col?>">
              <span style="font-size: 1.2rem;"></span>
              <span style="flex:1;"><?= $name ?? "Unknown" ?></span>
              <span style="opacity:0.6;">→</span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div style="color:red;">
          <?= "No languages found, please contact admin" ?>
        </div>
      <?php endif; ?>
    </div>
    <div style="border-top: 1px solid rgba(255,255,255,0.08); padding: 8px 16px; font-size: 0.6rem; text-align: center; color: rgba(255,255,255,0.45); background: rgba(0,0,0,0.3);">
      CTRX Translations - By <a href="https://www.tiktok.com/@codebasixs" target="_blank" style="text-decoration:none;">CodeYro</a>
    </div>
  </div>
</div>

<script>
  (function() {
    var widgetContainer = document.getElementById('transFloatWidget');
    var circleBtn = document.getElementById('transCircleBtn');
    var expandedPanel = document.getElementById('transExpandedPanel');
    var collapseBtn = document.getElementById('collapseBtn');
    var closePanelBtn = document.getElementById('closePanelBtn');
    var dragHandle = document.getElementById('expandedDragHandle');

    var isOpen = false;
    var isCircleDragging = false;
    var isPanelDragging = false;
    var dragStartX = 0, dragStartY = 0;
    var panelDragOffsetX = 0, panelDragOffsetY = 0;
    var startLeft = 0, startTop = 0;
    var touchStartTime = 0;
    var STORAGE_KEY = 'ctrx_trans_float_widget_tyroneleeemz';

    function snapToEdge(left, top) {
      var winWidth = window.innerWidth;
      var winHeight = window.innerHeight;
      var circleSize = 33;
      var padding = 10;
      var distanceToLeft = left;
      var distanceToRight = winWidth - (left + circleSize);
      var distanceToTop = top;
      var distanceToBottom = winHeight - (top + circleSize);
      var minDistance = Math.min(distanceToLeft, distanceToRight, distanceToTop, distanceToBottom);
      if (minDistance === distanceToLeft) {
        left = padding;
      } else if (minDistance === distanceToRight) {
        left = winWidth - circleSize - padding;
      } else if (minDistance === distanceToTop) {
        top = padding;
      } else {
        top = winHeight - circleSize - padding;
      }
      return { left: left, top: top };
    }

    function loadSavedPosition() {
      var saved = localStorage.getItem(STORAGE_KEY);
      var left, top;
      var winWidth = window.innerWidth;
      var winHeight = window.innerHeight;
      var circleSize = 33;
      var padding = 10;
      if (saved) {
        try {
          var pos = JSON.parse(saved);
          left = pos.left;
          top = pos.top;
          if (left >= 0 && left <= winWidth - circleSize && top >= 0 && top <= winHeight - circleSize) {
            var snapped = snapToEdge(left, top);
            widgetContainer.style.left = snapped.left + 'px';
            widgetContainer.style.top = snapped.top + 'px';
            widgetContainer.style.right = 'auto';
            widgetContainer.style.bottom = 'auto';
            return;
          }
        } catch(e) {}
      }
      left = winWidth - circleSize - padding;
      top = winHeight - circleSize - padding;
      widgetContainer.style.left = left + 'px';
      widgetContainer.style.top = '50%';
      widgetContainer.style.right = 'auto';
      widgetContainer.style.bottom = 'auto';
    }

    function savePosition() {
      var rect = widgetContainer.getBoundingClientRect();
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        left: rect.left,
        top: rect.top
      }));
    }

    function positionExpandedNearCircle() {
      var circleRect = widgetContainer.getBoundingClientRect();
      var panelWidth = 300;
      var panelHeight = expandedPanel.offsetHeight || 400;
      var winWidth = window.innerWidth;
      var winHeight = window.innerHeight;
      var targetLeft = circleRect.left + 16.5 - (panelWidth / 2);
      var targetTop = circleRect.top - panelHeight - 10;
      if (targetLeft < 10) targetLeft = 10;
      if (targetLeft + panelWidth > winWidth - 10) targetLeft = winWidth - panelWidth - 10;
      if (targetTop < 10) {
        targetTop = circleRect.top + 43;
      }
      if (targetTop + panelHeight > winHeight - 10) {
        targetTop = winHeight - panelHeight - 10;
      }
      expandedPanel.style.left = targetLeft + 'px';
      expandedPanel.style.top = targetTop + 'px';
      expandedPanel.style.right = 'auto';
      expandedPanel.style.bottom = 'auto';
    }

    function openPanel() {
      if (isOpen) return;
      positionExpandedNearCircle();
      expandedPanel.style.transform = 'scale(1)';
      expandedPanel.style.opacity = '1';
      expandedPanel.style.pointerEvents = 'auto';
      isOpen = true;
    }

    function closePanel() {
      if (!isOpen) return;
      expandedPanel.style.transform = 'scale(0.95)';
      expandedPanel.style.opacity = '0';
      expandedPanel.style.pointerEvents = 'none';
      isOpen = false;
    }

    function getClientPoint(e) {
      if (e.touches) {
        return { clientX: e.touches[0].clientX, clientY: e.touches[0].clientY };
      }
      return { clientX: e.clientX, clientY: e.clientY };
    }

    function onCircleTouchStart(e) {
      e.preventDefault();
      e.stopPropagation();
      var point = getClientPoint(e);
      var rect = widgetContainer.getBoundingClientRect();
      dragStartX = point.clientX - rect.left;
      dragStartY = point.clientY - rect.top;
      startLeft = rect.left;
      startTop = rect.top;
      touchStartTime = Date.now();
      isCircleDragging = true;
      document.body.style.userSelect = 'none';
      document.body.style.webkitUserSelect = 'none';
      document.addEventListener('touchmove', onCircleTouchMove, { passive: false });
      document.addEventListener('touchend', onCircleTouchEnd);
    }

    function onCircleTouchMove(e) {
      if (!isCircleDragging) return;
      e.preventDefault();
      var point = getClientPoint(e);
      var newLeft = point.clientX - dragStartX;
      var newTop = point.clientY - dragStartY;
      var winWidth = window.innerWidth;
      var winHeight = window.innerHeight;
      var circleSize = 33;
      newLeft = Math.min(Math.max(newLeft, 0), winWidth - circleSize);
      newTop = Math.min(Math.max(newTop, 0), winHeight - circleSize);
      widgetContainer.style.left = newLeft + 'px';
      widgetContainer.style.top = newTop + 'px';
      widgetContainer.style.right = 'auto';
      widgetContainer.style.bottom = 'auto';
    }

    function onCircleTouchEnd(e) {
      isCircleDragging = false;
      document.body.style.userSelect = '';
      document.body.style.webkitUserSelect = '';
      document.removeEventListener('touchmove', onCircleTouchMove);
      document.removeEventListener('touchend', onCircleTouchEnd);
      var rect = widgetContainer.getBoundingClientRect();
      var moved = Math.abs(rect.left - startLeft) > 5 || Math.abs(rect.top - startTop) > 5;
      if (!moved && (Date.now() - touchStartTime) < 300) {
        if (isOpen) {
          closePanel();
        } else {
          openPanel();
        }
      }
      var snapped = snapToEdge(rect.left, rect.top);
      widgetContainer.style.left = snapped.left + 'px';
      widgetContainer.style.top = snapped.top + 'px';
      savePosition();
      if (isOpen) {
        positionExpandedNearCircle();
      }
    }

    function onCircleMouseDown(e) {
      e.preventDefault();
      e.stopPropagation();
      var rect = widgetContainer.getBoundingClientRect();
      dragStartX = e.clientX - rect.left;
      dragStartY = e.clientY - rect.top;
      startLeft = rect.left;
      startTop = rect.top;
      isCircleDragging = true;
      document.body.style.userSelect = 'none';
      document.addEventListener('mousemove', onCircleMouseMove);
      document.addEventListener('mouseup', onCircleMouseUp);
    }

    function onCircleMouseMove(e) {
      if (!isCircleDragging) return;
      e.preventDefault();
      var newLeft = e.clientX - dragStartX;
      var newTop = e.clientY - dragStartY;
      var winWidth = window.innerWidth;
      var winHeight = window.innerHeight;
      var circleSize = 33;
      newLeft = Math.min(Math.max(newLeft, 0), winWidth - circleSize);
      newTop = Math.min(Math.max(newTop, 0), winHeight - circleSize);
      widgetContainer.style.left = newLeft + 'px';
      widgetContainer.style.top = newTop + 'px';
      widgetContainer.style.right = 'auto';
      widgetContainer.style.bottom = 'auto';
    }

    function onCircleMouseUp(e) {
      isCircleDragging = false;
      document.body.style.userSelect = '';
      document.removeEventListener('mousemove', onCircleMouseMove);
      document.removeEventListener('mouseup', onCircleMouseUp);
      var rect = widgetContainer.getBoundingClientRect();
      var moved = Math.abs(rect.left - startLeft) > 3 || Math.abs(rect.top - startTop) > 3;
      if (!moved) {
        if (isOpen) {
          closePanel();
        } else {
          openPanel();
        }
      }
      var snapped = snapToEdge(rect.left, rect.top);
      widgetContainer.style.left = snapped.left + 'px';
      widgetContainer.style.top = snapped.top + 'px';
      savePosition();
      if (isOpen) {
        positionExpandedNearCircle();
      }
    }

    function onPanelDragStart(e) {
      if (!isOpen) return;
      if (collapseBtn.contains(e.target) || closePanelBtn.contains(e.target)) return;
      if (!dragHandle.contains(e.target)) return;
      e.preventDefault();
      e.stopPropagation();
      isPanelDragging = true;
      var point = getClientPoint(e);
      var rect = expandedPanel.getBoundingClientRect();
      panelDragOffsetX = point.clientX - rect.left;
      panelDragOffsetY = point.clientY - rect.top;
      expandedPanel.style.cursor = 'grabbing';
      dragHandle.style.cursor = 'grabbing';
      document.body.style.userSelect = 'none';
      document.body.style.webkitUserSelect = 'none';
      document.addEventListener('mousemove', onPanelDragMove);
      document.addEventListener('mouseup', onPanelDragEnd);
      document.addEventListener('touchmove', onPanelDragMove, { passive: false });
      document.addEventListener('touchend', onPanelDragEnd);
    }

    function onPanelDragMove(e) {
      if (!isPanelDragging) return;
      e.preventDefault();
      var point = getClientPoint(e);
      var newLeft = point.clientX - panelDragOffsetX;
      var newTop = point.clientY - panelDragOffsetY;
      var winWidth = window.innerWidth;
      var winHeight = window.innerHeight;
      var panelWidth = expandedPanel.offsetWidth;
      var panelHeight = expandedPanel.offsetHeight;
      newLeft = Math.min(Math.max(newLeft, 10), winWidth - panelWidth - 10);
      newTop = Math.min(Math.max(newTop, 10), winHeight - panelHeight - 10);
      expandedPanel.style.left = newLeft + 'px';
      expandedPanel.style.top = newTop + 'px';
      expandedPanel.style.right = 'auto';
      expandedPanel.style.bottom = 'auto';
    }

    function onPanelDragEnd(e) {
      isPanelDragging = false;
      expandedPanel.style.cursor = '';
      if (dragHandle) dragHandle.style.cursor = 'grab';
      document.body.style.userSelect = '';
      document.body.style.webkitUserSelect = '';
      document.removeEventListener('mousemove', onPanelDragMove);
      document.removeEventListener('mouseup', onPanelDragEnd);
      document.removeEventListener('touchmove', onPanelDragMove);
      document.removeEventListener('touchend', onPanelDragEnd);
    }

    function handleOutsideClick(e) {
      if (isOpen && !expandedPanel.contains(e.target) && !circleBtn.contains(e.target)) {
        closePanel();
      }
    }

    expandedPanel.addEventListener('click', function(e) {
      e.stopPropagation();
    });

    circleBtn.addEventListener('touchstart', onCircleTouchStart, { passive: false });
    circleBtn.addEventListener('mousedown', onCircleMouseDown);

    collapseBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      closePanel();
    });

    closePanelBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      widgetContainer.style.display = 'none';
      localStorage.removeItem(STORAGE_KEY);
    });

    dragHandle.addEventListener('mousedown', onPanelDragStart);
    dragHandle.addEventListener('touchstart', onPanelDragStart, { passive: false });

    document.addEventListener('click', handleOutsideClick);

    window.addEventListener('resize', function() {
      var rect = widgetContainer.getBoundingClientRect();
      var winWidth = window.innerWidth;
      var winHeight = window.innerHeight;
      var circleSize = 33;
      var newLeft = Math.min(Math.max(rect.left, 0), winWidth - circleSize);
      var newTop = Math.min(Math.max(rect.top, 0), winHeight - circleSize);
      var snapped = snapToEdge(newLeft, newTop);
      widgetContainer.style.left = snapped.left + 'px';
      widgetContainer.style.top = snapped.top + 'px';
      savePosition();
      if (isOpen) {
        positionExpandedNearCircle();
      }
    });

    var links = document.querySelectorAll('.transItem');
    links.forEach(function(link) {
      link.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(3px)';
      });
      link.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
      });
    });

    loadSavedPosition();
    circleBtn.addEventListener('mouseenter', function() {
      this.style.transform = 'scale(1.05)';
      this.style.boxShadow = '0 8px 20px rgba(0,0,0,0.3)';
    });
    circleBtn.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
      this.style.boxShadow = '0 6px 16px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.2)';
    });
  })();
</script>