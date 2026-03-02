<?php
session_start();
include "db.php";



if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Create</title>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    background:
        radial-gradient(circle at top left, rgba(139,92,246,0.35), transparent 40%),
        radial-gradient(circle at bottom right, rgba(0,255,200,0.25), transparent 40%),
        #050510;
    color:#fff;
    display:flex;
}

/* TOPBAR */
.topbar{
    position:fixed;
    top:0;
    left:0;
    right:0;
    padding:20px 40px;
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(20px);
    border-bottom:1px solid rgba(255,255,255,0.1);
    display:flex;
    justify-content:space-between;
    align-items:center;
    z-index:1000;
}

.nav a{
    margin-left:20px;
    text-decoration:none;
    color:#00ffc8;
    font-weight:600;
}

/* SIDEBAR */
.sidebar{
    width:220px;
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(20px);
    border-right:1px solid rgba(255,255,255,0.1);
    padding-top:100px;
    height:100vh;
}

.sidebar h3{
    padding-left:20px;
    font-family:'Orbitron',sans-serif;
}

.sidebar a{
    display:block;
    padding:12px 20px;
    text-decoration:none;
    color:#fff;
}

.sidebar a:hover{
    background:rgba(255,255,255,0.08);
}

/* MAIN */
.main{
    flex:1;
    padding:120px 40px 40px 40px;
}

h2{
    font-family:'Orbitron',sans-serif;
    margin-bottom:20px;
}

/* BOARD */
.board{
    display:flex;
    gap:20px;
    align-items:flex-start;
}

.column{
    width:250px;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(25px);
    border:1px solid rgba(255,255,255,0.15);
    padding:15px;
    border-radius:20px;
    min-height:300px;
    position:relative;
}

.column-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.menu-btn{
    background:none;
    border:none;
    font-size:18px;
    cursor:pointer;
    color:#fff;
}

.dropdown{
    display:none;
    position:absolute;
    right:0;
    top:25px;
    background:rgba(20,20,30,0.95);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:10px;
    padding:5px 0;
    z-index:100;
}

.dropdown button{
    background:none;
    border:none;
    width:100%;
    text-align:left;
    padding:8px 12px;
    cursor:pointer;
    color:#fff;
}

.dropdown button:hover{
    background:rgba(255,255,255,0.1);
}

.new-btn{
    margin-top:10px;
    background:linear-gradient(135deg,#8b5cf6,#00ffc8);
    color:#fff;
    border:none;
    padding:8px 12px;
    border-radius:20px;
    cursor:pointer;
    font-weight:600;
}

.add-group-btn{
    height:300px;
    width:250px;
    border:2px dashed rgba(255,255,255,0.3);
    background:rgba(255,255,255,0.05);
    border-radius:20px;
    cursor:pointer;
    font-weight:bold;
    color:#00ffc8;
}

.idea-card{
    background:rgba(255,255,255,0.08);
    padding:10px;
    border-radius:12px;
    margin-top:10px;
    border:1px solid rgba(255,255,255,0.15);
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.6);
    justify-content:center;
    align-items:center;
    z-index:2000;
}

.modal-content{
    background:rgba(20,20,30,0.95);
    backdrop-filter:blur(25px);
    width:500px;
    padding:25px;
    border-radius:20px;
    border:1px solid rgba(255,255,255,0.15);
}

.modal-content input,
.modal-content textarea{
    width:100%;
    padding:10px;
    margin-bottom:15px;
    border:1px solid rgba(255,255,255,0.15);
    border-radius:12px;
    background:rgba(255,255,255,0.05);
    color:#fff;
}
</style>
</head>

<body>

<div class="topbar">
    <span style="font-weight:bold;">SocialHub</span>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="analytics.php">Analytics</a>
        <a href="email.php">Email</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="sidebar">
    <h3>Create</h3>
    <a href="#">Ideas</a>
    <a href="#">Templates</a>
</div>

<div class="main">

<h2>Ideas Board</h2>

<div class="board" id="board">

<div class="column">
    <div class="column-header">
        <h4>Unassigned</h4>
        <div class="menu">
            <button class="menu-btn" onclick="toggleMenu(this)">⋮</button>
            <div class="dropdown">
                <button onclick="deleteGroup(this)">Delete</button>
            </div>
        </div>
    </div>
    <div class="ideas"></div>
    <button class="new-btn" onclick="openIdeaModal(this)">+ New Idea</button>
</div>

<button class="add-group-btn" onclick="openGroupModal()">+ Add New Group</button>

</div>
</div>

<!-- IDEA MODAL -->
<div class="modal" id="ideaModal">
    <div class="modal-content">
        <h3>Create Idea</h3>
        <input type="text" id="ideaTitle" placeholder="Idea title">
        <textarea id="ideaContent" placeholder="Write idea..."></textarea>
        <div style="text-align:right;">
            <button onclick="closeIdeaModal()">Cancel</button>
            <button onclick="saveIdea()">Save</button>
        </div>
    </div>
</div>

<!-- GROUP MODAL -->
<div class="modal" id="groupModal">
    <div class="modal-content">
        <h3>Create New Group</h3>
        <input type="text" id="groupName" placeholder="Group name">
        <div style="text-align:right;">
            <button onclick="closeGroupModal()">Cancel</button>
            <button onclick="createGroup()">Create</button>
        </div>
    </div>
</div>

<script>
let currentColumn = null;

function openIdeaModal(button){
    currentColumn = button.closest(".column");
    document.getElementById("ideaModal").style.display = "flex";
}

function closeIdeaModal(){
    document.getElementById("ideaModal").style.display = "none";
}

function saveIdea(){
    let title = document.getElementById("ideaTitle").value;
    let content = document.getElementById("ideaContent").value;
    if(title.trim() === "") return;

    let card = document.createElement("div");
    card.className = "idea-card";
    card.innerHTML = "<strong>"+title+"</strong><br>"+content;

    currentColumn.querySelector(".ideas").appendChild(card);

    document.getElementById("ideaTitle").value = "";
    document.getElementById("ideaContent").value = "";
    closeIdeaModal();
}

function openGroupModal(){
    document.getElementById("groupModal").style.display = "flex";
}

function closeGroupModal(){
    document.getElementById("groupModal").style.display = "none";
}

function createGroup(){
    let name = document.getElementById("groupName").value;
    if(name.trim() === "") return;

    let board = document.getElementById("board");
    let addButton = document.querySelector(".add-group-btn");

    let column = document.createElement("div");
    column.className = "column";
    column.innerHTML = `
        <div class="column-header">
            <h4>${name}</h4>
            <div class="menu">
                <button class="menu-btn" onclick="toggleMenu(this)">⋮</button>
                <div class="dropdown">
                    <button onclick="deleteGroup(this)">Delete</button>
                </div>
            </div>
        </div>
        <div class="ideas"></div>
        <button class="new-btn" onclick="openIdeaModal(this)">+ New Idea</button>
    `;

    board.insertBefore(column, addButton);

    document.getElementById("groupName").value = "";
    closeGroupModal();
}

function toggleMenu(btn){
    let dropdown = btn.nextElementSibling;
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}

function deleteGroup(button){
    if(confirm("Delete this group?")){
        let column = button.closest(".column");
        column.remove();
    }
}
</script>

</body>
</html>