  <?php
  session_start();
  include("connection.php");

  if (!isset($_SESSION['teacher_email'])) {
      header("Location: login_teacher.php");
      exit;
  }

  $email = $_SESSION['teacher_email'];
  $query = "SELECT * FROM teachers WHERE email = $1";
  $result = pg_query_params($con, $query, [$email]);
  $teacher = pg_fetch_assoc($result);

  if (!$teacher) {
      echo "Teacher not found.";
      exit;
  }

  $profilePicture = !empty($teacher['profile_picture']) 
      ? 'uploads/' . htmlspecialchars($teacher['profile_picture']) 
      : 'images/default_profile.png';

  $course_query = "
      SELECT c.id, c.course_name, c.description
      FROM courses c
      JOIN teacher_courses tc ON c.id = tc.course_id
      WHERE tc.teacher_id = $1
  ";
  $course_result = pg_query_params($con, $course_query, [$teacher['id']]);
  $full_name = htmlspecialchars($teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']);
  ?>
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset="UTF-8">
      <title>Teacher Dashboard</title>
      <link rel="stylesheet" href="style.css">
      <style>
    
      body.teacher-view { 
        font-family: Arial, sans-serif; 
        background: #f5f5f5; 
        margin: 0; 
        padding: 0; 
      }
      .profile-container {
        padding: 20px;
        text-align: center; 
      }
      .course-section {
         padding: 20px; 
        }
      
      .modal { 
        display: none; 
        position: fixed; 
        top:0; 
        left:0; 
        width:100%; 
        height:100%; 
        background: rgba(0,0,0,0.5);
        justify-content: center; 
        align-items: center; 
        z-index: 1000; 
      }
      .modal-content { 
        background: #fff; 
        border-radius: 8px; 
        padding: 25px; 
        max-width: 700px; 
        width: 95%; 
        max-height: 90vh; 
        overflow-y: auto; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
       }
      .modal-content h3 { 
        margin-top: 0; 
        color: #6a11cb; 
      }
      .modal-content textarea { 
        width: 100%; 
        min-height: 100px; 
        resize: vertical; 
        border: 1px solid #ccc; 
        border-radius: 6px; 
        padding: 8px; 
        font-size: 14px; 
      }
      .btn-group { 
        margin-top: 16px; 
        display: flex; 
        justify-content: flex-end; 
        gap: 10px; 
      }
      .btn-group button { 
        background: #6a11cb; 
        color: #fff; 
        padding: 8px 16px; 
        border-radius: 6px; 
        border:none; 
        cursor:pointer; 
      }
      .btn-group .cancel-btn { 
        background: #999; 
      }

      .lecture-block span, 
      .lecture-block {
       display: flex;
       align-items: center;
       gap: 10px; 
      flex-wrap: wrap; 
      margin-bottom: 8px;
}

    .lecture-block span,
    .lecture-block label,
    .lecture-block input[type="file"] {
    font-size: 0.85rem;
}

    .lecture-block .remove-lecture-btn {
    font-size: 0.85rem;
    flex-shrink: 0; 
}
      .lecture-actions { 
        margin-top:6px; 
        display:flex; 
        gap:10px; 
        flex-wrap:wrap; }
      .remove-lecture-btn { 
        background:#d9534f; 
        color:#fff; 
        border:none; 
        padding:6px 10px; 
        border-radius:5px; 
        cursor:pointer; }
      .confirm-modal {
       position: fixed;
       top: 0;
       left: 0;
       width: 100%;
       height: 100%;
       background: rgba(0,0,0,0.5);
       display: flex;
       justify-content: center;
       align-items: center;
       z-index: 1000;
}

.confirm-modal-content {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
}
.confirm-modal-content button {
  margin: 0 10px;
}

      </style>
  </head>
  <body class="teacher-view">

  <?php include("navbar_teacher.php"); ?>

  <div class="profile-container">
      <h1>Welcome, <?php echo $full_name; ?>!</h1>
  </div>

  <div class="course-section">
      <?php if (pg_num_rows($course_result) > 0): ?>
        <table class="styled-table">
          <thead>
              <tr>
                  <th>Course name</th>
                  <th>Description</th>
                  <th>Lectures</th>
                  <th>Students</th>
                  <th>Homework</th>
                  <th>Grades</th>
              </tr>
          </thead>
          <tbody>
              <?php while ($course = pg_fetch_assoc($course_result)): ?>
                  <tr data-course-id="<?= (int)$course['id'] ?>">
                      <td><?= htmlspecialchars($course['course_name']) ?></td>
                      <td>
                          <button class="btn edit-desc-btn" data-course-id="<?= (int)$course['id'] ?>">Edit</button>
                      </td>
                      <td>
                          <button class="btn edit-lectures-btn" data-course-id="<?= (int)$course['id'] ?>">Edit</button>
                      </td>
                      <td>
                          <a href="manage_course_students.php?course_id=<?= (int)$course['id'] ?>" class="btn">Manage</a>
                      </td>
                      <td>
                          <a href="add_homework.php?course_id=<?= (int)$course['id'] ?>" class="btn">Add</a>
                      </td>
                      <td>
                          <a href="grade_homeworks.php?course_id=<?= (int)$course['id'] ?>" class="btn">Add</a>
                      </td>
                  </tr>
              <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
          <p><em>You do not have any assigned courses yet.</em></p>
      <?php endif; ?>
  </div>

<div id="descModal" class="modal">
  <div class="modal-content">
    <h3>Edit Course Description</h3>
    <form id="descForm">
      <textarea name="description" placeholder="Enter course description"></textarea>
      <div class="btn-group">
        <button type="button" class="cancel-btn" id="cancelDescBtn">Cancel</button>
        <button type="submit" id="saveDescBtn">Save</button>
      </div>
    </form>
  </div>
</div>

<div id="lecturesModal" class="modal">
  <div id="confirmModal" class="confirm-modal" style="display:none;">
    <div class="confirm-modal-content">
      <p id="confirmText">Are you sure?</p>
      <button id="confirmYes">Yes</button>
      <button id="confirmNo">No</button>
    </div>
  </div>
  <div class="modal-content">
    <h3>Edit Course Lectures</h3>
    <form id="lecturesForm" enctype="multipart/form-data">
      <div id="lecturesContainer"></div>
      <button type="button" id="addLectureBtn">+ Add Lecture</button>
      <div class="btn-group">
        <button type="button" class="cancel-btn" id="cancelLecturesBtn">Cancel</button>
        <button type="submit" id="saveLecturesBtn">Save All</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const descModal = document.getElementById('descModal');
  const descForm = document.getElementById('descForm');
  let currentCourseIdDesc = null;

  document.querySelectorAll('.edit-desc-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      currentCourseIdDesc = btn.getAttribute('data-course-id');
      descModal.style.display = 'flex';

      fetch('get_course_description.php?course_id=' + encodeURIComponent(currentCourseIdDesc), { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
          const textarea = descForm.querySelector('textarea[name="description"]');
          if (textarea) textarea.value = data.description || '';
        })
        .catch(err => {
          console.error('Fetch error:', err);
          descForm.description.value = '';
        });
    });
  });

  document.getElementById('cancelDescBtn').addEventListener('click', () => {
    descModal.style.display = 'none';
  });

  descForm.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(descForm);
    fd.append('course_id', currentCourseIdDesc);

    fetch('update_course_description.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if(data.success){
          alert('Saved!');
          descModal.style.display = 'none';
        } else {
          alert('Error: ' + (data.error || 'unknown'));
        }
      })
      .catch(err => console.error('Save error:', err));
  });

  window.addEventListener('click', e => {
    if(e.target === descModal) descModal.style.display = 'none';
  });

  function showConfirm(message, onConfirm) {
    const confirmModal = document.getElementById('confirmModal');
    const confirmText = document.getElementById('confirmText');
    const btnYes = document.getElementById('confirmYes');
    const btnNo = document.getElementById('confirmNo');

    confirmText.textContent = message;
    confirmModal.style.display = 'flex';

    const cleanUp = () => {
      confirmModal.style.display = 'none';
      btnYes.removeEventListener('click', yesHandler);
      btnNo.removeEventListener('click', noHandler);
    };

    const yesHandler = () => {
      cleanUp();
      onConfirm();
    };

    const noHandler = () => {
      cleanUp();
    };

    btnYes.addEventListener('click', yesHandler);
    btnNo.addEventListener('click', noHandler);
  }


  const lecturesModal = document.getElementById('lecturesModal');
  const lecturesForm = document.getElementById('lecturesForm');
  const lecturesContainer = document.getElementById('lecturesContainer');
  let currentCourseIdLectures = null;

  function reindex() {
    lecturesContainer.querySelectorAll('.lecture-block').forEach((block, i) => {
      const idInput = block.querySelector('input[type="hidden"][name$="[id]"]');
      if (idInput) idInput.name = `lectures[${i}][id]`;
      const desc = block.querySelector('textarea[name$="[description]"]');
      if (desc) desc.name = `lectures[${i}][description]`;
      const file = block.querySelector('input[type="file"][name$="[file]"]');
      if (file) file.name = `lectures[${i}][file]`;
    });
  }

  function addLectureBlock({id='', description='', filename=''} = {}) {
    const idx = lecturesContainer.children.length;
    const wrapper = document.createElement('div');
    wrapper.className = 'lecture-block';
    if(id) wrapper.id = `lecture-${id}`;

    let html = '';
    if (id) html += `<input type="hidden" name="lectures[${idx}][id]" value="${id}">`;
    html += `<label>Description:</label><textarea name="lectures[${idx}][description]">${description || ''}</textarea>`;

    if (filename) {
      html += `<span>Current: <a href="uploads/course_lectures/${encodeURIComponent(filename)}" target="_blank">${filename}</a></span>
               <label>Replace: <input type="file" name="lectures[${idx}][file]"></label>
               <button type="button" class="remove-lecture-btn">Remove</button>`;
    } else {
      html += `<label>Upload file: <input type="file" name="lectures[${idx}][file]"></label>
               <button type="button" class="remove-lecture-btn">Remove</button>`;
    }

    wrapper.innerHTML = html;
    lecturesContainer.appendChild(wrapper);

    const removeBtn = wrapper.querySelector('.remove-lecture-btn');
    removeBtn.addEventListener('click', () => {
      if(id) {
        const lectureId = id; 
        if(!confirm('Are you sure you want to delete this lecture?')) return;
        fetch('delete_course_lecture.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ lecture_id: lectureId })
        })
        .then(r => r.json())
        .then(data => {
            if(data.success){
              wrapper.remove();
              reindex();
            } else alert('Error: ' + (data.error || 'Unknown'));
        });
      } else {
        wrapper.remove();
        reindex();
      }
    });
}

function reindex() {
  const blocks = lecturesContainer.querySelectorAll('.lecture-block');
  blocks.forEach((block, idx) => {
    const desc = block.querySelector('textarea');
    if(desc) desc.name = `lectures[${idx}][description]`;

    const fileInput = block.querySelector('input[type="file"]');
    if(fileInput) fileInput.name = `lectures[${idx}][file]`;

    const hiddenId = block.querySelector('input[type="hidden"]');
    if(hiddenId) hiddenId.name = `lectures[${idx}][id]`;
  });
}

  document.querySelectorAll('.edit-lectures-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      currentCourseIdLectures = btn.getAttribute('data-course-id');
      lecturesContainer.innerHTML = '';
      fetch('get_course_lectures.php?course_id=' + encodeURIComponent(currentCourseIdLectures))
        .then(r => r.json())
        .then(data => {
          data.lectures.forEach(lec => addLectureBlock(lec));
          lecturesModal.style.display = 'flex';
        });
    });
  });

  document.getElementById('addLectureBtn').addEventListener('click', () => {
    addLectureBlock();
    reindex();
  });

  document.getElementById('cancelLecturesBtn').addEventListener('click', () => {
    lecturesModal.style.display = 'none';
  });

  lecturesForm.addEventListener('submit', e => {
    e.preventDefault();
    if (!currentCourseIdLectures) return;
    if(lecturesContainer.children.length === 0) {
      alert('Add at least one lecture.');
      return;
    }

    const fd = new FormData(lecturesForm);
    fd.append('course_id', currentCourseIdLectures);

    fetch('update_course_lectures.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          alert('Lectures saved successfully!');
          lecturesModal.style.display = 'none';
        } else {
          alert('Error: ' + (data.error || 'unknown'));
        }
      })
      .catch(err => console.error('Save error:', err));
  });

  window.addEventListener('click', e => {
    if(e.target === lecturesModal) lecturesModal.style.display = 'none';
  });
});
</script>

  </body>
  </html>
