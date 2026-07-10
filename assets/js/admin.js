( function( $, wp ) {
	'use strict';

	if ( ! window.MFO_DATA || ! wp || ! wp.apiFetch ) {
		return;
	}

	var data = window.MFO_DATA;
		var state = {
			tree: data.tree || [],
			flat: data.flat || [],
			currentFolder: readFolderFromUrl(),
			busy: false,
			filterViews: [],
			uploaders: [],
			pendingFolderMove: null,
			treeSaveTimer: null
		};

	wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( data.restNonce ) );

	function readFolderFromUrl() {
		var params = new URLSearchParams( window.location.search );
		return params.has( 'mfo_folder' ) ? parseInt( params.get( 'mfo_folder' ), 10 ) : -1;
	}

	function api( path, options ) {
		return wp.apiFetch( $.extend( {
			path: data.restPath + path
		}, options || {} ) );
	}

	function escapeHtml( value ) {
		return $( '<div>' ).text( value == null ? '' : String( value ) ).html();
	}

	function folderLabel( folder ) {
		return new Array( ( folder.level || 0 ) + 1 ).join( '— ' ) + folder.name;
	}

	function notify( message, type ) {
		var $notice = $( '<div class="mfo-notice" role="status"></div>' )
			.addClass( type === 'error' ? 'is-error' : 'is-success' )
			.text( message );
		$( 'body' ).append( $notice );
		window.setTimeout( function() {
			$notice.addClass( 'is-visible' );
		}, 20 );
		window.setTimeout( function() {
			$notice.removeClass( 'is-visible' );
			window.setTimeout( function() {
				$notice.remove();
			}, 200 );
		}, 2600 );
	}

	function errorMessage( error ) {
		return error && error.message ? error.message : data.strings.error;
	}

	function dialogHtml() {
		return '<div class="mfo-dialog-backdrop" hidden>' +
			'<section class="mfo-dialog" role="dialog" aria-modal="true" aria-labelledby="mfo-dialog-title">' +
				'<header class="mfo-dialog-header">' +
					'<span class="mfo-dialog-icon dashicons dashicons-category" aria-hidden="true"></span>' +
					'<h2 id="mfo-dialog-title"></h2>' +
					'<button type="button" class="mfo-dialog-close" aria-label="' + escapeHtml( data.strings.cancel ) + '"><span class="dashicons dashicons-no-alt"></span></button>' +
				'</header>' +
				'<div class="mfo-dialog-content">' +
					'<p class="mfo-dialog-message"></p>' +
					'<div class="mfo-dialog-field">' +
						'<label for="mfo-dialog-name">' + escapeHtml( data.strings.folderName ) + '</label>' +
						'<input type="text" id="mfo-dialog-name" class="regular-text" autocomplete="off" maxlength="200">' +
						'<p class="mfo-dialog-error" role="alert"></p>' +
					'</div>' +
				'</div>' +
				'<footer class="mfo-dialog-footer">' +
					'<button type="button" class="button mfo-dialog-cancel">' + escapeHtml( data.strings.cancel ) + '</button>' +
					'<button type="button" class="button button-primary mfo-dialog-submit"></button>' +
				'</footer>' +
			'</section>' +
		'</div>';
	}

	function openDialog( options ) {
		if ( ! $( '.mfo-dialog-backdrop' ).length ) {
			$( 'body' ).append( dialogHtml() );
		}

		var deferred = $.Deferred();
		var $backdrop = $( '.mfo-dialog-backdrop' );
		var $dialog = $backdrop.find( '.mfo-dialog' );
		var $input = $dialog.find( '#mfo-dialog-name' );
		var $field = $dialog.find( '.mfo-dialog-field' );
		var $message = $dialog.find( '.mfo-dialog-message' );
		var $error = $dialog.find( '.mfo-dialog-error' );
		var $submit = $dialog.find( '.mfo-dialog-submit' );
		var hasInput = options.input !== false;
		var previousFocus = document.activeElement;
		var closed = false;

		$dialog.find( '#mfo-dialog-title' ).text( options.title || '' );
		$message.text( options.message || '' ).toggle( !! options.message );
		$field.toggle( hasInput );
		$input.val( options.value || '' ).attr( 'aria-invalid', 'false' );
		$error.text( '' ).hide();
		$submit
			.text( options.submitText || data.strings.saveChanges )
			.toggleClass( 'mfo-button-danger', !! options.danger );
		$dialog.toggleClass( 'is-danger', !! options.danger );

		function close( value ) {
			if ( closed ) {
				return;
			}
			closed = true;
			$backdrop.attr( 'hidden', 'hidden' );
			$( 'body' ).removeClass( 'mfo-dialog-open' );
			$backdrop.off( '.mfoDialog' );
			$( document ).off( '.mfoDialog' );
			if ( previousFocus && typeof previousFocus.focus === 'function' ) {
				previousFocus.focus();
			}
			deferred.resolve( value );
		}

		function submit() {
			if ( ! hasInput ) {
				close( true );
				return;
			}

			var value = $input.val().trim();
			if ( ! value ) {
				$input.attr( 'aria-invalid', 'true' );
				$error.text( data.strings.folderNameRequired ).show();
				$input.trigger( 'focus' );
				return;
			}

			close( value );
		}

		$backdrop
			.removeAttr( 'hidden' )
			.on( 'click.mfoDialog', function( event ) {
				if ( event.target === this ) {
					close( null );
				}
			} );
		$dialog.find( '.mfo-dialog-close, .mfo-dialog-cancel' ).on( 'click.mfoDialog', function() {
			close( null );
		} );
		$submit.on( 'click.mfoDialog', submit );
		$input.on( 'input.mfoDialog', function() {
			$input.attr( 'aria-invalid', 'false' );
			$error.text( '' ).hide();
		} );
		$( document ).on( 'keydown.mfoDialog', function( event ) {
			if ( 'Escape' === event.key ) {
				event.preventDefault();
				close( null );
			} else if ( 'Enter' === event.key && hasInput && document.activeElement === $input[0] ) {
				event.preventDefault();
				submit();
			} else if ( 'Tab' === event.key ) {
				var $focusable = $dialog.find( 'button:visible, input:visible' );
				var first = $focusable[0];
				var last = $focusable[ $focusable.length - 1 ];
				if ( event.shiftKey && document.activeElement === first ) {
					event.preventDefault();
					last.focus();
				} else if ( ! event.shiftKey && document.activeElement === last ) {
					event.preventDefault();
					first.focus();
				}
			}
		} );

		$( 'body' ).addClass( 'mfo-dialog-open' );
		window.setTimeout( function() {
			if ( hasInput ) {
				$input.trigger( 'focus' );
				$input[0].select();
			} else {
				$submit.trigger( 'focus' );
			}
		}, 20 );

		return deferred.promise();
	}

	function setData( response ) {
		state.tree = response.tree || [];
		state.flat = response.flat || [];
		data.totalCount = response.total_count || 0;
			data.uncategorizedCount = response.uncategorized_count || 0;
			renderSidebarTree();
			refreshFolderSelects();
			refreshMediaFilters();
		}

	function folderNodeHtml( folder ) {
		var children = ( folder.children || [] ).map( folderNodeHtml ).join( '' );
		var hasChildren = children !== '';

		return '<li class="mfo-folder-node" data-folder-id="' + folder.id + '">' +
			'<div class="mfo-folder-row" tabindex="0">' +
				'<button type="button" class="mfo-disclosure' + ( hasChildren ? '' : ' is-empty' ) + '" aria-label="' + escapeHtml( data.strings.folders ) + '">' +
					'<span class="dashicons dashicons-arrow-right-alt2"></span>' +
				'</button>' +
				'<span class="dashicons dashicons-category mfo-folder-icon" aria-hidden="true"></span>' +
				'<span class="mfo-folder-name">' + escapeHtml( folder.name ) + '</span>' +
				'<span class="mfo-folder-count">' + folder.count + '</span>' +
				'<span class="mfo-folder-actions">' +
					'<button type="button" class="mfo-icon-button mfo-add-child" title="' + escapeHtml( data.strings.newSubfolder ) + '"><span class="dashicons dashicons-plus-alt2"></span></button>' +
					'<button type="button" class="mfo-icon-button mfo-rename" title="' + escapeHtml( data.strings.rename ) + '"><span class="dashicons dashicons-edit"></span></button>' +
					'<button type="button" class="mfo-icon-button mfo-delete" title="' + escapeHtml( data.strings.delete ) + '"><span class="dashicons dashicons-trash"></span></button>' +
				'</span>' +
			'</div>' +
			'<ul class="mfo-tree-list mfo-child-list' + ( hasChildren ? '' : ' is-empty' ) + '">' + children + '</ul>' +
		'</li>';
	}

	function sidebarHtml() {
		return '<button type="button" class="mfo-mobile-toggle button" aria-expanded="false" title="' + escapeHtml( data.strings.openFolders ) + '">' +
				'<span class="dashicons dashicons-category"></span>' +
			'</button>' +
			'<div class="mfo-sidebar-backdrop"></div>' +
			'<aside id="mfo-sidebar" aria-label="' + escapeHtml( data.strings.folders ) + '">' +
				'<header class="mfo-sidebar-header">' +
					'<strong>' + escapeHtml( data.strings.folders ) + '</strong>' +
					'<span class="mfo-sidebar-tools">' +
						'<button type="button" class="mfo-icon-button mfo-new-root" title="' + escapeHtml( data.strings.newFolder ) + '"><span class="dashicons dashicons-plus-alt2"></span></button>' +
						'<button type="button" class="mfo-icon-button mfo-refresh" title="' + escapeHtml( data.strings.refresh ) + '"><span class="dashicons dashicons-update"></span></button>' +
						'<button type="button" class="mfo-icon-button mfo-sidebar-close" title="' + escapeHtml( data.strings.closeFolders ) + '"><span class="dashicons dashicons-no-alt"></span></button>' +
					'</span>' +
				'</header>' +
				'<div class="mfo-system-folders">' +
					'<button type="button" class="mfo-system-folder" data-folder-id="-1"><span class="dashicons dashicons-images-alt2"></span><span>' + escapeHtml( data.strings.allFolders ) + '</span><b>' + data.totalCount + '</b></button>' +
					'<button type="button" class="mfo-system-folder" data-folder-id="0"><span class="dashicons dashicons-open-folder"></span><span>' + escapeHtml( data.strings.uncategorized ) + '</span><b>' + data.uncategorizedCount + '</b></button>' +
				'</div>' +
				'<div class="mfo-root-drop"><span class="dashicons dashicons-move"></span>' + escapeHtml( data.strings.rootDrop ) + '</div>' +
				'<div class="mfo-tree-scroll"><ul class="mfo-tree-list mfo-root-list"></ul></div>' +
				'<footer class="mfo-sidebar-footer">' +
					'<button type="button" class="button button-primary mfo-move-selected">' + escapeHtml( data.strings.moveSelected ) + '</button>' +
				'</footer>' +
			'</aside>';
	}

	function initSidebar() {
		if ( ! data.isMediaPage || $( '#mfo-sidebar' ).length ) {
			return;
		}

		$( 'body' ).append( sidebarHtml() ).addClass( 'mfo-sidebar-active' );
		renderSidebarTree();
		bindSidebarEvents();
		initAttachmentDragging();
	}

	function renderSidebarTree() {
		if ( ! $( '#mfo-sidebar' ).length ) {
			return;
		}

		$( '.mfo-root-list' ).html( state.tree.map( folderNodeHtml ).join( '' ) );
		$( '.mfo-system-folder[data-folder-id="-1"] b' ).text( data.totalCount );
		$( '.mfo-system-folder[data-folder-id="0"] b' ).text( data.uncategorizedCount );
		markSelectedFolder();
		initTreeInteractions();
	}

	function markSelectedFolder() {
		$( '.mfo-folder-row, .mfo-system-folder' ).removeClass( 'is-selected' );
			if ( state.currentFolder > 0 ) {
				$( '.mfo-folder-node[data-folder-id="' + state.currentFolder + '"] > .mfo-folder-row' ).addClass( 'is-selected' );
			} else {
				$( '.mfo-system-folder[data-folder-id="' + state.currentFolder + '"]' ).addClass( 'is-selected' );
			}
			$( '.mfo-move-selected' ).prop( 'disabled', state.currentFolder === -1 );
	}

	function schedulePersistTree( move ) {
		if ( move ) {
			state.pendingFolderMove = move;
		}

		window.clearTimeout( state.treeSaveTimer );
		state.treeSaveTimer = window.setTimeout( function() {
			var pending = state.pendingFolderMove;
			state.pendingFolderMove = null;
			state.treeSaveTimer = null;

			if ( pending ) {
				var $dragNode = $( '.mfo-folder-node[data-folder-id="' + pending.folderId + '"]' );
				if ( 0 === pending.parentId ) {
					$( '.mfo-root-list' ).append( $dragNode );
				} else {
					var $targetNode = $( '.mfo-folder-node[data-folder-id="' + pending.parentId + '"]' );
					if ( $targetNode.length && ! $targetNode.is( $dragNode ) && ! $targetNode.closest( $dragNode ).length ) {
						$targetNode.children( '.mfo-child-list' ).append( $dragNode );
						$targetNode.addClass( 'is-open' );
						$targetNode.children( '.mfo-folder-row' ).find( '.mfo-disclosure' ).removeClass( 'is-empty' );
					}
				}
			}

			persistTree();
		}, 60 );
	}

	function initTreeInteractions() {
		$( '.mfo-tree-list' ).sortable( {
			connectWith: '.mfo-tree-list',
			items: '> .mfo-folder-node',
			handle: '> .mfo-folder-row',
			placeholder: 'mfo-sort-placeholder',
			tolerance: 'pointer',
			start: function( event, ui ) {
				state.pendingFolderMove = null;
				$( '#mfo-sidebar' ).addClass( 'mfo-is-sorting' );
				ui.item.addClass( 'mfo-folder-being-dragged' );
			},
			stop: function( event, ui ) {
				ui.item.removeClass( 'mfo-folder-being-dragged' );
				$( '#mfo-sidebar' ).removeClass( 'mfo-is-sorting' );
				$( '.mfo-folder-row, .mfo-root-drop' ).removeClass( 'is-drop-target' );
				schedulePersistTree();
			}
		} ).disableSelection();

		$( '.mfo-folder-row' ).droppable( {
			accept: function( draggable ) {
				var $dragNode = $( draggable );
				if ( $dragNode.hasClass( 'attachment' ) ) {
					return true;
				}
				if ( ! $dragNode.hasClass( 'mfo-folder-node' ) ) {
					return false;
				}
				var $targetNode = $( this ).closest( '.mfo-folder-node' );
				return ! $targetNode.is( $dragNode ) && ! $targetNode.closest( $dragNode ).length;
			},
			tolerance: 'pointer',
			greedy: true,
			over: function( event, ui ) {
				$( this ).addClass( ui.draggable.hasClass( 'attachment' ) ? 'is-attachment-drop-target' : 'is-drop-target' );
			},
			out: function() {
				$( this ).removeClass( 'is-drop-target is-attachment-drop-target' );
			},
			drop: function( event, ui ) {
				$( this ).removeClass( 'is-drop-target is-attachment-drop-target' );

				if ( ui.draggable.hasClass( 'attachment' ) ) {
					var ids = selectedAttachmentIds();
					var draggedId = parseInt( ui.draggable.data( 'id' ), 10 );
					if ( ids.indexOf( draggedId ) === -1 ) {
						ids = [ draggedId ];
					}
					moveAttachments( ids, parseInt( $( this ).closest( '.mfo-folder-node' ).data( 'folder-id' ), 10 ) );
					return;
				}

				var $targetNode = $( this ).closest( '.mfo-folder-node' );
				var $dragNode = ui.draggable;

				if ( $targetNode.is( $dragNode ) || $targetNode.closest( $dragNode ).length ) {
					return;
				}

				schedulePersistTree( {
					folderId: parseInt( $dragNode.data( 'folder-id' ), 10 ),
					parentId: parseInt( $targetNode.data( 'folder-id' ), 10 )
				} );
			}
		} );

		$( '.mfo-root-drop' ).droppable( {
			accept: '.mfo-folder-node',
			tolerance: 'pointer',
			hoverClass: 'is-drop-target',
			drop: function( event, ui ) {
				schedulePersistTree( {
					folderId: parseInt( ui.draggable.data( 'folder-id' ), 10 ),
					parentId: 0
				} );
			}
		} );

		initFolderAttachmentDrops();
	}

	function serializeTree() {
		var items = [];

		function walk( $list, parent ) {
			$list.children( '.mfo-folder-node' ).each( function( index ) {
				var $node = $( this );
				var id = parseInt( $node.data( 'folder-id' ), 10 );
				items.push( { id: id, parent: parent, order: index } );
				walk( $node.children( '.mfo-child-list' ), id );
			} );
		}

		walk( $( '.mfo-root-list' ), 0 );
		return items;
	}

	function persistTree() {
		if ( state.busy ) {
			return;
		}

		state.busy = true;
		api( '/folders/reorder', {
			method: 'POST',
			data: { items: serializeTree() }
		} ).then( function( response ) {
			setData( response );
			notify( data.strings.saved );
		} ).catch( function( error ) {
			notify( errorMessage( error ), 'error' );
			refreshFolders();
		} ).finally( function() {
			state.busy = false;
		} );
	}

	function bindSidebarEvents() {
		$( document ).on( 'click.mfo', '.mfo-mobile-toggle', function() {
			$( 'body' ).addClass( 'mfo-sidebar-open' );
			$( this ).attr( 'aria-expanded', 'true' );
		} );

		$( document ).on( 'click.mfo', '.mfo-sidebar-close, .mfo-sidebar-backdrop', function() {
			$( 'body' ).removeClass( 'mfo-sidebar-open' );
			$( '.mfo-mobile-toggle' ).attr( 'aria-expanded', 'false' );
		} );

		$( document ).on( 'click.mfo', '.mfo-system-folder', function() {
			selectFolder( parseInt( $( this ).data( 'folder-id' ), 10 ) );
		} );

		$( document ).on( 'click.mfo', '.mfo-folder-row', function( event ) {
			if ( $( event.target ).closest( 'button' ).length ) {
				return;
			}
			selectFolder( parseInt( $( this ).closest( '.mfo-folder-node' ).data( 'folder-id' ), 10 ) );
		} );

		$( document ).on( 'dblclick.mfo', '.mfo-folder-row', function() {
			$( this ).closest( '.mfo-folder-node' ).toggleClass( 'is-open' );
		} );

		$( document ).on( 'click.mfo', '.mfo-disclosure', function( event ) {
			event.stopPropagation();
			$( this ).closest( '.mfo-folder-node' ).toggleClass( 'is-open' );
		} );

		$( document ).on( 'click.mfo', '.mfo-new-root', function() {
			createFolder( 0 );
		} );

		$( document ).on( 'click.mfo', '.mfo-add-child', function( event ) {
			event.stopPropagation();
			createFolder( parseInt( $( this ).closest( '.mfo-folder-node' ).data( 'folder-id' ), 10 ) );
		} );

		$( document ).on( 'click.mfo', '.mfo-rename', function( event ) {
			event.stopPropagation();
			var $node = $( this ).closest( '.mfo-folder-node' );
			renameFolder( parseInt( $node.data( 'folder-id' ), 10 ), $node.find( '> .mfo-folder-row .mfo-folder-name' ).text() );
		} );

		$( document ).on( 'click.mfo', '.mfo-delete', function( event ) {
			event.stopPropagation();
			var $node = $( this ).closest( '.mfo-folder-node' );
			deleteFolder(
				parseInt( $node.data( 'folder-id' ), 10 ),
				$node.find( '> .mfo-folder-row .mfo-folder-name' ).text()
			);
		} );

			$( document ).on( 'click.mfo', '.mfo-refresh', refreshFolders );
			$( document ).on( 'click.mfo', '.mfo-move-selected', function() {
				if ( state.currentFolder === -1 ) {
					notify( data.strings.selectDestination, 'error' );
					return;
				}
				moveAttachments( selectedAttachmentIds(), state.currentFolder );
			} );
	}

	function createFolder( parent ) {
		openDialog( {
			title: parent > 0 ? data.strings.createChildTitle : data.strings.createFolderTitle,
			submitText: data.strings.create,
			input: true
		} ).then( function( name ) {
			if ( name == null ) {
				return;
			}

			api( '/folders', {
				method: 'POST',
				data: { name: name, parent: parent }
			} ).then( setData ).catch( function( error ) {
				notify( errorMessage( error ), 'error' );
			} );
		} );
	}

	function renameFolder( id, oldName ) {
		openDialog( {
			title: data.strings.renameFolderTitle,
			value: oldName,
			submitText: data.strings.saveChanges,
			input: true
		} ).then( function( name ) {
			if ( name == null || name === oldName ) {
				return;
			}

			api( '/folders/' + id, {
				method: 'PUT',
				data: { name: name }
			} ).then( setData ).catch( function( error ) {
				notify( errorMessage( error ), 'error' );
			} );
		} );
	}

	function deleteFolder( id, folderName ) {
		var message = data.strings.deleteConfirm.replace( '%s', function() {
			return folderName;
		} );

		openDialog( {
			title: data.strings.deleteFolderTitle,
			message: message,
			submitText: data.strings.confirmDelete,
			input: false,
			danger: true
		} ).then( function( confirmed ) {
			if ( ! confirmed ) {
				return;
			}

			api( '/folders/' + id, {
				method: 'DELETE'
			} ).then( function( response ) {
				setData( response );
				if ( state.currentFolder > 0 && ! state.flat.some( function( folder ) {
					return folder.id === state.currentFolder;
				} ) ) {
					selectFolder( 0 );
				}
			} ).catch( function( error ) {
				notify( errorMessage( error ), 'error' );
			} );
		} );
	}

	function refreshFolders() {
		api( '/folders' ).then( setData ).catch( function( error ) {
			notify( errorMessage( error ), 'error' );
		} );
	}

	function selectFolder( folderId ) {
		state.currentFolder = folderId;
		markSelectedFolder();
		setUploaderFolder( folderId > 0 ? folderId : 0 );

		if ( data.isMediaPage && data.isListMode ) {
			var url = new URL( data.mediaUrl, window.location.origin );
			url.searchParams.set( 'mode', 'list' );
			url.searchParams.set( 'mfo_folder', String( folderId ) );
			window.location.assign( url.toString() );
			return;
		}

		applyFolderToMediaFrame( folderId );
		$( 'body' ).removeClass( 'mfo-sidebar-open' );
	}

		function applyFolderToMediaFrame( folderId ) {
			var frames = [];
			if ( wp.media && wp.media.frame ) {
				frames.push( wp.media.frame );
			}

			state.filterViews.forEach( function( view ) {
				if ( view && view.model ) {
					view.model.set( 'mfo_folder', folderId );
				}
			} );

		frames.forEach( function( frame ) {
			try {
				var library = frame.state().get( 'library' );
				if ( library && library.props ) {
					library.props.set( 'mfo_folder', folderId );
				}
			} catch ( ignore ) {
				// Some media frame states do not expose a library collection.
			}
		} );
	}

	function selectedAttachmentIds() {
		var ids = [];

		$( '.attachments .attachment.selected' ).each( function() {
			var id = parseInt( $( this ).data( 'id' ), 10 );
			if ( id ) {
				ids.push( id );
			}
		} );

		$( '#the-list th.check-column input[type="checkbox"]:checked' ).each( function() {
			var match = String( this.id || '' ).match( /cb-select-(\d+)/ );
			if ( match ) {
				ids.push( parseInt( match[1], 10 ) );
			}
		} );

		return Array.from( new Set( ids ) );
	}

		function moveAttachments( ids, folderId ) {
			if ( folderId < 0 ) {
				notify( data.strings.selectDestination, 'error' );
				return;
			}

			if ( ! ids.length ) {
				notify( data.strings.selectMedia, 'error' );
			return;
		}

		api( '/assign', {
			method: 'POST',
				data: {
					attachment_ids: ids,
					folder_id: folderId
				}
		} ).then( function( response ) {
			setData( response.folders );
			notify( data.strings.moved );
			applyFolderToMediaFrame( state.currentFolder );
			if ( data.isListMode ) {
				window.location.reload();
			}
		} ).catch( function( error ) {
			notify( errorMessage( error ), 'error' );
		} );
	}

	function initAttachmentDragging() {
		var observer = new MutationObserver( function() {
			makeAttachmentsDraggable();
		} );

		observer.observe( document.body, { childList: true, subtree: true } );
		makeAttachmentsDraggable();
	}

	function makeAttachmentsDraggable() {
		$( '.attachments .attachment:not(.mfo-draggable)' ).addClass( 'mfo-draggable' ).draggable( {
			appendTo: 'body',
			helper: function() {
				var count = Math.max( 1, selectedAttachmentIds().length );
				return $( '<div class="mfo-drag-helper"><span class="dashicons dashicons-format-image"></span>' + count + '</div>' );
			},
			cursorAt: { left: 20, top: 20 },
			zIndex: 100000,
			distance: 8
		} );
	}

	function initFolderAttachmentDrops() {
		$( '.mfo-system-folder[data-folder-id="0"]' ).droppable( {
			accept: '.attachment',
			tolerance: 'pointer',
			greedy: true,
			hoverClass: 'is-attachment-drop-target',
			drop: function( event, ui ) {
				var ids = selectedAttachmentIds();
				var draggedId = parseInt( ui.draggable.data( 'id' ), 10 );
				if ( ids.indexOf( draggedId ) === -1 ) {
					ids = [ draggedId ];
				}

				var $folder = $( this ).closest( '[data-folder-id]' );
				moveAttachments( ids, parseInt( $folder.data( 'folder-id' ), 10 ) );
			}
		} );
	}

		function refreshFolderSelects() {
			var uploadOptions = '<option value="0">' + escapeHtml( data.strings.uncategorized ) + '</option>';
			state.flat.forEach( function( folder ) {
				uploadOptions += '<option value="' + folder.id + '">' + escapeHtml( folderLabel( folder ) ) + '</option>';
			} );
			$( '#mfo-upload-folder' ).html( uploadOptions ).val( state.currentFolder > 0 ? state.currentFolder : 0 );
		}

		function refreshMediaFilters() {
			state.filterViews.forEach( function( view ) {
				if ( view && typeof view.refreshFolders === 'function' ) {
					view.refreshFolders();
				}
			} );
		}

		function installMediaFilter() {
		if ( ! wp.media || ! wp.media.view || ! wp.media.view.AttachmentsBrowser || wp.media.view.AttachmentsBrowser.prototype.mfoInstalled ) {
			return;
		}

			var FolderFilter = wp.media.view.AttachmentFilters.extend( {
				id: 'mfo-media-folder-filter',
				className: 'attachment-filters mfo-folder-filter',
				attributes: {
					'aria-label': data.strings.folders
				},
				initialize: function() {
					if ( typeof this.model.get( 'mfo_folder' ) === 'undefined' ) {
						this.model.set( 'mfo_folder', state.currentFolder, { silent: true } );
					}
					wp.media.view.AttachmentFilters.prototype.initialize.apply( this, arguments );
				},
				createFilters: function() {
					var filters = {};
					filters[ '-1' ] = {
						text: data.strings.allFolders,
						props: { mfo_folder: -1 },
						priority: 10
					};
					filters[ '0' ] = {
						text: data.strings.uncategorized,
						props: { mfo_folder: 0 },
						priority: 20
					};
					state.flat.forEach( function( folder, index ) {
						filters[ String( folder.id ) ] = {
							text: folderLabel( folder ),
							props: { mfo_folder: folder.id },
							priority: 30 + index
					};
					} );
					this.filters = filters;
				},
				refreshFolders: function() {
					var view = this;
					this.createFilters();
					this.$el.empty();
					Object.keys( this.filters ).sort( function( left, right ) {
						return view.filters[ left ].priority - view.filters[ right ].priority;
					} ).forEach( function( key ) {
						$( '<option>' ).val( key ).text( view.filters[ key ].text ).appendTo( view.$el );
					} );
					this.select();
				}
			} );

			var originalCreateToolbar = wp.media.view.AttachmentsBrowser.prototype.createToolbar;
			wp.media.view.AttachmentsBrowser.prototype.createToolbar = function() {
				originalCreateToolbar.apply( this, arguments );
				var folderFilter = new FolderFilter( {
					controller: this.controller,
					model: this.collection.props,
					priority: -75
				} );
				state.filterViews.push( folderFilter );
				this.toolbar.set( 'mfoFolderFilter', folderFilter.render() );
				this.toolbar.$el.find( '.media-toolbar-secondary' ).addClass( 'mfo-has-folder-filter' );
			};
		wp.media.view.AttachmentsBrowser.prototype.mfoInstalled = true;

		$( document ).on( 'change.mfo', '.mfo-folder-filter', function() {
			var folderId = parseInt( $( this ).val(), 10 );
			if ( ! isNaN( folderId ) ) {
				state.currentFolder = folderId;
				setUploaderFolder( folderId > 0 ? folderId : 0 );
				markSelectedFolder();
			}
		} );
	}

		function setUploaderFolder( folderId ) {
			if ( window._wpPluploadSettings && window._wpPluploadSettings.defaults && window._wpPluploadSettings.defaults.multipart_params ) {
				window._wpPluploadSettings.defaults.multipart_params.mfo_folder = folderId;
		}
			if ( window.uploader && window.uploader.settings && window.uploader.settings.multipart_params ) {
				window.uploader.settings.multipart_params.mfo_folder = folderId;
			}
			state.uploaders.forEach( function( uploaderInstance ) {
				uploaderInstance.param( 'mfo_folder', folderId );
			} );
		}

	function patchUploader() {
		setUploaderFolder( state.currentFolder > 0 ? state.currentFolder : 0 );

		if ( ! wp.Uploader || wp.Uploader.prototype.mfoInstalled ) {
			return;
		}

			var originalInit = wp.Uploader.prototype.init;
			wp.Uploader.prototype.init = function() {
				if ( state.uploaders.indexOf( this ) === -1 ) {
					state.uploaders.push( this );
				}
				this.param( 'mfo_folder', state.currentFolder > 0 ? state.currentFolder : 0 );
			if ( originalInit ) {
				return originalInit.apply( this, arguments );
			}
		};
		wp.Uploader.prototype.mfoInstalled = true;
	}

	$( document ).on( 'change.mfo', '#mfo-upload-folder', function() {
		var folderId = parseInt( $( this ).val(), 10 ) || 0;
		state.currentFolder = folderId;
		setUploaderFolder( folderId );
	} );

	patchUploader();
	installMediaFilter();

	$( function() {
		initSidebar();
		refreshFolderSelects();
	} );
} )( jQuery, window.wp );
